<?php

namespace App\Traits;

trait HasContextualPricing
{
    /**
     * Dynamic price context - can be set by Livewire components or controllers
     */
    public $contextOrderTypeId = null;
    public $contextDeliveryAppId = null;

    /**
     * In-memory cache for resolved price (per request only, not persistent)
     * This prevents duplicate queries when accessing price multiple times
     */
    protected $resolvedContextualPrice = null;

    /**
     * Set price context for this model instance
     *
     * @param int|null $orderTypeId
     * @param int|null $deliveryAppId
     * @return self
     */
    public function setPriceContext(?int $orderTypeId, ?int $deliveryAppId = null): self
    {
        // Only reset cache if context actually changed
        if ($this->contextOrderTypeId !== $orderTypeId || $this->contextDeliveryAppId !== $deliveryAppId) {
            $this->resolvedContextualPrice = null;
        }

        $this->contextOrderTypeId = $orderTypeId;
        $this->contextDeliveryAppId = $deliveryAppId;
        return $this;
    }

    /**
     * Get the contextual price based on set context
     * This is a computed property: $model->contextual_price
     *
     * @return float
     */
    public function getContextualPriceAttribute(): float
    {
        if ($this->contextOrderTypeId !== null) {
            // Use cached price if available
            if ($this->resolvedContextualPrice !== null) {
                return $this->resolvedContextualPrice;
            }

            // Resolve and cache the price
            $this->resolvedContextualPrice = $this->resolveContextualPrice(
                $this->contextOrderTypeId,
                $this->contextDeliveryAppId
            );

            return $this->resolvedContextualPrice;
        }

        // Fallback to base price
        return (float)($this->attributes['price'] ?? 0);
    }

    /**
     * Override the price attribute to use contextual pricing when context is set
     * This makes $model->price work contextually
     *
     * @param mixed $value
     * @return float
     */
    public function getPriceAttribute($value): float
    {
        // If context is set, resolve contextual price
        if ($this->contextOrderTypeId !== null) {
            // Use cached price if available
            if ($this->resolvedContextualPrice !== null) {
                return $this->resolvedContextualPrice;
            }

            // Resolve and cache the price
            $this->resolvedContextualPrice = $this->resolveContextualPrice(
                $this->contextOrderTypeId,
                $this->contextDeliveryAppId
            );

            return $this->resolvedContextualPrice;
        }

        // Otherwise return base price from database
        return (float)($value ?? 0);
    }

    /**
     * Resolve contextual price from pricing table
     * Must be implemented by the model using this trait
     *
     * @param int $orderTypeId
     * @param int|null $deliveryAppId
     * @return float
     */
    abstract protected function resolveContextualPrice(int $orderTypeId, ?int $deliveryAppId = null): float;
}

