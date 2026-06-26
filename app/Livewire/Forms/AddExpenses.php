<?php

namespace App\Livewire\Forms;

use App\Helper\Files;
use App\Models\Expenses;
use Livewire\Component;
use App\Models\ExpenseCategory;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;

class AddExpenses extends Component
{
    use WithFileUploads, LivewireAlert;

    public $expense_category_id;
    public $amount;
    public $expense_date;
    public $payment_status = 'pending';
    public $payment_date;
    public $payment_due_date;
    public $payment_method;
    public $description;
    public $expense_receipt;
    public $receipt;
    public $newCategoryName;
    public $expense_title;
    public $showExpenseCategoryModal = false;
    public $closeModal = false;
    public $showAddExpenses = false;

    public $paymentMethods = [
        'cash' => 'modules.expenses.methods.cash',
        'bank_transfer' => 'modules.expenses.methods.bank_transfer',
        'credit_card' => 'modules.expenses.methods.credit_card',
        'debit_card' => 'modules.expenses.methods.debit_card',
        'check' => 'modules.expenses.methods.check',
        'digital_wallet' => 'modules.expenses.methods.digital_wallet'
    ];

    protected $listeners = [
        'closeModal',

    ];

    #[On('hideExpenseCategoryModal')]
    public function hideExpenseCategoryModal()
    {
        $this->showExpenseCategoryModal = false;
    }
    public function updatedExpenseReceipt()
    {
        $this->validateReceipt();
    }

    public function validateReceipt()
    {
        // Clear any existing errors for this field
        $this->resetErrorBag('expense_receipt');

        if ($this->expense_receipt) {
            // Check if it's an image file (not PDF)
            $mimeType = $this->expense_receipt->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                // Validate image dimensions
                $imageInfo = @getimagesize($this->expense_receipt->getRealPath());
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];

                    // Only show error if dimensions are smaller than recommended (600 × 400)
                    // Images larger than recommended size are acceptable and will not show an error
                    if ($width < 600 || $height < 400) {
                        $this->addError('expense_receipt', __('modules.expenses.imageDimensionsTooSmall', [
                            'width' => 600,
                            'height' => 400,
                            'currentWidth' => $width,
                            'currentHeight' => $height
                        ]));
                    }
                }
            }
        }
    }

    public function save()
    {
        $this->validate([
            'expense_title' => 'required|string',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_status' => 'required|in:pending,paid',
            'payment_date' => 'nullable|date',
            'payment_due_date' => 'nullable|date',
            'payment_method' => $this->payment_status === 'paid' ? 'required|string' : 'nullable|string',
            'description' => 'nullable|string|max:1000',
            'expense_receipt' => 'nullable|file|max:5120', // Optional & supports any file up to 5MB
        ]);

        // Validate receipt dimensions if it's an image
        $this->validateReceipt();

        // Check if there are validation errors
        if ($this->getErrorBag()->has('expense_receipt')) {
            return;
        }

        $expense = Expenses::create([
            'expense_title' => $this->expense_title,
            'expense_category_id' => $this->expense_category_id,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
            'payment_status' => $this->payment_status,
            'payment_date' => $this->payment_date,
            'payment_due_date' => $this->payment_due_date,
            'payment_method' => $this->payment_method,
            'description' => $this->description,
        ]);

        if ($this->expense_receipt) {
            $receiptPath = Files::uploadLocalOrS3($this->expense_receipt, 'expense');
            $expense->update(['receipt_path' => $receiptPath]);
        }

        $this->reset();
        $this->dispatch('expenseAdded');

        $this->alert('success', __('messages.expenseAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('livewire.forms.add-expenses', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }
}
