@extends('layouts.app')

@section('content')

    <livewire:pos.pos :orderID="$orderID" :key="$orderID" />

@endsection


