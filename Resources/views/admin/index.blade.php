@extends('smartcars3phpvms7api::layouts.admin')

@section('title', 'smartCARS 3 API')
@section('content')
    <div class="card border-blue-bottom">
        <div class="content">
            <form action="{{ url('/admin/smartcars3phpvms7api/import' }}" method="post">
                {{ csrf_field() }}
                <button type="submit" class="btn btn-primary">Import PIREPs</button>
            </form>
        </div>
    </div>
@endsection
