@extends('layouts.app')

@section('title')
<i class="material-icons left">chevron_right</i>@lang('issue.report')
@endsection

@section('content')
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">@lang('issue.report')</span>
                <p>@lang('issue.report_long_description')</p>
                
            </div>
        </div>
    </div>
</div>
@endsection
