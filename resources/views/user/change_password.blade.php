@extends('layouts.master')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-key me-2 text-muted"></i> პაროლის შეცვლა
            </div>
            <div class="card-body p-4">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <i class="fa fa-check me-1"></i> {{ session('success') }}
                </div>
                @endif

                <form method="POST" action="{{ route('user.change-password') }}">
                    @csrf
                    <div class="mb-3 {{ $errors->has('current_password') ? 'has-validation' : '' }}">
                        <label class="form-label fw-semibold">მიმდინარე პაროლი</label>
                        <input type="password" name="current_password" class="form-control {{ $errors->has('current_password') ? 'is-invalid' : '' }}" placeholder="მიმდინარე პაროლი" required>
                        @if($errors->has('current_password'))
                        <div class="invalid-feedback">{{ $errors->first('current_password') }}</div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ახალი პაროლი</label>
                        <input type="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="მინიმუმ 6 სიმბოლო" required>
                        @if($errors->has('password'))
                        <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                        @endif
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">გაიმეორეთ ახალი პაროლი</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="გაიმეორეთ" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i> შეცვლა
                        </button>
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">გაუქმება</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection