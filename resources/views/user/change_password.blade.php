@extends('layouts.master')

@section('content')
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">პაროლის შეცვლა</h3>
    </div>

    <div class="box-body">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">

                {{-- Success message --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fa fa-check"></i> {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('user.change-password') }}">
                    @csrf

                    {{-- Current password --}}
                    <div class="form-group {{ $errors->has('current_password') ? 'has-error' : '' }}">
                        <label>მიმდინარე პაროლი</label>
                        <input type="password"
                               name="current_password"
                               class="form-control"
                               placeholder="შეიყვანეთ მიმდინარე პაროლი"
                               required>
                        @if ($errors->has('current_password'))
                            <span class="help-block text-danger">
                                {{ $errors->first('current_password') }}
                            </span>
                        @endif
                    </div>

                    {{-- New password --}}
                    <div class="form-group {{ $errors->has('password') ? 'has-error' : '' }}">
                        <label>ახალი პაროლი</label>
                        <input type="password"
                               name="password"
                               class="form-control"
                               placeholder="მინიმუმ 6 სიმბოლო"
                               required>
                        @if ($errors->has('password'))
                            <span class="help-block text-danger">
                                {{ $errors->first('password') }}
                            </span>
                        @endif
                    </div>

                    {{-- Confirm new password --}}
                    <div class="form-group">
                        <label>გაიმეორეთ ახალი პაროლი</label>
                        <input type="password"
                               name="password_confirmation"
                               class="form-control"
                               placeholder="გაიმეორეთ ახალი პაროლი"
                               required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> შეცვლა
                        </button>
                        <a href="{{ url()->previous() }}" class="btn btn-default">
                            გაუქმება
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection