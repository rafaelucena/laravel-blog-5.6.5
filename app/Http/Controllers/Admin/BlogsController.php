<?php

namespace App\Http\Controllers\Admin;

use App\Blog;
use App\User;
use App\Category;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreBlogPost;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BlogsController extends Controller
{
    /**
     * Enforce middleware.
     */
    public function __construct()
    {
        $this->middleware('role:view_all_blog', ['only' => ['index', 'blogsData', 'trashed', 'blogsAjaxTrashedData']]);
        $this->middleware('role:view_blog', ['only' => ['show']]);

        $this->middleware('role:add_blog', ['only' => ['create','store']]);

        $this->middleware('role:edit_blog', ['only' => ['update', 'edit', 'updateActiveStatus']]);

        $this->middleware('role:delet_blog', ['only' => ['destroy', 'restore', 'permanentDelet', 'emptyTrash']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $trashed_items = Blog::onlyTrashed()->count();
        return view('admin/blogs/index', ['trashed_items_count' => $trashed_items]);
    }

    /**
     * index blogs - Process datatables ajax request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function blogsData()
    {
        $blogs = Blog::join('users', 'blogs.user_id', '=', 'users.id')
                        ->select(['blogs.id', 'blogs.title', 'blogs.user_id', 'blogs.is_active', 'users.name', 'blogs.created_at']);

        return Datatables::of($blogs)
                ->editColumn('created_at', function ($model) {
                    return $model->created_at->format('F d, Y h:i A');
                })
                ->editColumn('is_active', function ($model) {
                    if ($model->is_active == 0) {
                        return '<div class="text-danger">No <span class="badge badge-light"><i class="fas fa-times"></i></span></div>';
                    } else {
                        return '<div class="text-success">Yes <span class="badge badge-light"><i class="fas fa-check"></i></span></div>';
                    }
                })
                ->editColumn('users.name', function ($model) {
                    return '<a href="'.route('users.show', $model->user_id).'" class="link">'.$model->name.' <i class="fas fa-external-link-alt"></i></a>';
                })
                ->addColumn('actions', function ($model) {
                    if ($model->is_active == 0) {
                        $publish_action = '<a class="dropdown-item" href="'.route('blogs.publishStatus', $model->id).'" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-check"></i> Publish</a>';
                    } else {
                        $publish_action = '<a class="dropdown-item" href="'.route('blogs.publishStatus', $model->id).'" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-times"></i> Unpublish</a>';
                    }
                    return '
                     <div class="dropdown float-right">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Action
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="'.route('blogs.show', $model->id).'"><i class="fas fa-eye"></i> View</a>
                            <a class="dropdown-item" href="'.route('blogs.edit', $model->id).'"><i class="fas fa-edit"></i> Edit</a>
                            '.$publish_action.'
                            <a class="dropdown-item text-danger" href="#" onclick="callDeletItem(\''.$model->id.'\', \'blogs\');"><i class="fas fa-trash"></i> Delet</a>
                        </div>
                    </div>';
                })
                ->rawColumns(['actions','users.name','is_active'])
                ->make(true);
    }



    /**
     * Display a listing of the trashed resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function trashed()
    {
        $trashed_items = Blog::onlyTrashed()->count();
        return view('admin/blogs/trashed-index', ['trashed_items_count' => $trashed_items]);
    }

    /**
     * trashed blogs - Process datatables ajax request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function blogsAjaxTrashedData()
    {
        $blogs = Blog::join('users', 'blogs.user_id', '=', 'users.id')
                        ->select(['blogs.id', 'blogs.title', 'blogs.user_id', 'users.name', 'blogs.deleted_at'])
                        ->onlyTrashed();

        return Datatables::of($blogs)
                ->editColumn('trashed_at', function ($model) {
                    return $model->deleted_at->format('F d, Y h:i A');
                })
                ->editColumn('users.name', function ($model) {
                    return '<a href="'.route('users.show', $model->user_id).'" class="link">'.$model->name.' <i class="fas fa-external-link-alt"></i></a>';
                })
                ->addColumn('actions', function ($model) {
                    return '
                     <div class="dropdown float-right">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Action
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="'.route('blogs.restore', $model->id).'" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-history"></i> Restore</a>
                            <a class="dropdown-item text-danger" href="'.route('blogs.permanentDelet', $model->id).'" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-trash"></i> Permanent Delet</a>
                        </div>
                    </div>';
                })
                ->rawColumns(['actions','users.name'])
                ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $authors = User::active()->whereHas('roles', function ($query) {
            $query->where('role', '=', 'add_blog');
        })->get();

        return view('admin/blogs/create', ['authors' => $authors]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBlogPost $request)
    {
        // Pre Validations are done in StoreBlogPost Request
        // Store the item

        // Store File & Get Path
        $imagePath = Storage::putFile('images', $request->file('image'));

        // Store & Get Categories
        $categoryArr = array();
        foreach ($request->categories as $category) {
            if (is_numeric($category)) {
                // Store in array
                $categoryArr[] = $category;
            } else {
                // if the item not numeric that means that its new item and we should create it
                // User Id will automatically set by mutator in Category model
                $newCategory = Category::create(['name' => $category, 'user_id' => Auth::user()->id]);
                // include the new item to array
                $categoryArr[] = $newCategory->id;
            }
        }

        // Step 1 - Init item Obj
        $blog = new Blog;

        // Set items fields
        $blog->title = $request->title;
        $blog->excerpt = $request->excerpt;
        $blog->description = $request->description;
        $blog->image = $imagePath;
        $blog->user_id = $request->user_id;
        $blog->is_active = $request->is_active;
        $blog->allow_comments = $request->allow_comments;

        // Step 2 - Save Item
        $blog->save();

        // Step 3 - Attach/Sync Related Items
        $blog->categories()->sync($categoryArr);

        // Back to index with success
        return redirect()->route('blogs.index')->with('custom_success', 'Blog has been added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $blog = Blog::findOrFail($id);
        return view('admin/blogs/show', ['blog' => $blog]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $blog = Blog::findOrFail($id);
        return view('admin/blogs/edit', ['blog' => $blog]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Update the is active status of specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateActiveStatus($id)
    {
        // get all trashed blogs and permanent Delet the blogs
        $blog = Blog::findOrFail($id);

        if ($blog->is_active == 0) {
            $blog->is_active = 1;
        } else {
            $blog->is_active = 0;
        }
        $status = $blog->save();

        if ($status) {
            // If success
            return back()->with('custom_success', 'Blog publish status updated.');
        } else {
            // If no success
            return back()->with('custom_errors', 'Failed to change publish status. Something went wrong.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Find the blog by $id
        $blog = Blog::findOrFail($id);

        // Soft Delet the blog and transfer to Trash Items
        $blog->delete();

        if ($blog->trashed()) {
            // If success
            return back()->with('custom_success', 'Blog has been deleted and transfered to trash items.');
        } else {
            // If no success
            return back()->with('custom_errors', 'Blog was not deleted. Something went wrong.');
        }
    }

    /**
     * Restore the specified resource from trashed storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        // Find the blog by $id
        $blog = Blog::onlyTrashed()->findOrFail($id);

        // Restore the blog
        $blog->restore();

        if (!$blog->trashed()) {
            // If success
            return back()->with('custom_success', 'Blog has been restored.');
        } else {
            // If no success
            return back()->with('custom_errors', 'Blog was not able to restore. Something went wrong.');
        }
    }

    /**
     * Permanent Delet the specified resource from trashed storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function permanentDelet($id)
    {
        // Find the blog by $id
        $blog = Blog::onlyTrashed()->findOrFail($id);

        // Permanent Delet the blog
        $status =$blog->forceDelete();

        if ($status) {
            // If success
            return back()->with('custom_success', 'Blog has been deleted permanently.');
        } else {
            // If no success
            return back()->with('custom_errors', 'Blog was not able to deleted permanently. Something went wrong.');
        }
    }

    /**
     * permanent delet all trashed items in the specified resource from trashed storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function emptyTrash()
    {
        // get all trashed blogs and permanent Delet the blogs
        $status = Blog::whereNotNull('deleted_at')->onlyTrashed()->forceDelete();

        if ($status) {
            // If success
            return back()->with('custom_success', 'Trash has been emptied.');
        } else {
            // If no success
            return back()->with('custom_errors', 'Failed to empty trash. Something went wrong.');
        }
    }
}
