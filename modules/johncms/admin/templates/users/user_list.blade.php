@extends('system::layout/default')
@section('content')
    <div class="vue_app">
        <user-list
            :roles="{{$data['roles']}}"
            create-user-url="<?= $data['createUserUrl'] ?>"
            delete-user-url="<?= $data['deleteUserUrl'] ?>"
            list-url="<?= route('admin.userList') ?>"
        ></user-list>
    </div>
@endsection
