@extends('admin/layouts.app')

@section('custom_css')
@endsection

@section('content')
<div class="card">
    <div class="card-header">Blogs - View <a href="{{ route('blogs.index') }}" class="btn btn-light float-right btn-sm "><i class="fas fa-chevron-left"></i> Go Back</a></div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <p><b>ID:</b> {{ $blog->id }}, <b>Views:</b> {{ $blog->views }}, <b>Created at</b>: {{ $blog->createdAt->format('Y-m-d') }}, <b>Updated At</b> {{ $blog->updatedAt ? $blog->updatedAt->format('Y-m-d') : '' }}</p>
                <div class="form-group">
                    <label for="title"><b>Title:</b></label>
                    <p>{{ $blog->title }}</p>
                    <p><b>Slug</b>: {{ $blog->slug }}</p>
                </div>
                <div class="form-group">
                    <label for="excerpt"><b>Excerpt/Summary: </b></label>
                    {{ $blog->excerpt }}
                </div>
                <div class="form-group">
                    <label for="description"><b>Description:</b></label>
                    {!! $blog->description !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="image"><b>Featured Image:</b></label>
                    <img class="img-fluid" src="{{ url( Storage::url($blog->image) ) }}" alt="{{ $blog->title }}">
                </div>
                <div class="form-group">
                    <label for="categories"><b>Categories:</b></label>
                    <ul>
                    @foreach($blog->blogCategories as $blogCategory)
                        <li>{{ $blogCategory->category->name }}</li>
                    @endforeach
                    </ul>
                </div>
                <div class="form-group">
                    <label for="user_id"><b>Author:</b></label>
                    {{ $blog->user->name }}
                </div>
                <div class="form-group">
                    <label for="is_active"><b>Publish:</b></label>
                    {{ $blog->isActive == 1 ? 'Yes' : 'No' }}
                </div>
                <div class="form-group">
                    <label for="test"><b>Test:</b></label>
                    {{ $persona->image }}
                </div>
                <div class="form-group">
                    <label for="allow_comments"><b>Allow Comments:</b></label>
                    {{ $blog->allowComments == 1 ? 'Yes' : 'No' }}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('custom_js')
@endsection
