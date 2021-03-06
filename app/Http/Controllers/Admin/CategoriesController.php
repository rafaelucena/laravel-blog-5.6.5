<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Models\Category;
use App\Http\Models\User;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\Datatables\Datatables;

class CategoriesController extends Controller
{
    /**
     * Enforce middleware.
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em);

        $this->middleware('role:view_all_category', ['only' => ['index','categoriesData']]);
        $this->middleware('role:view_category', ['only' => ['show']]);

        $this->middleware('role:add_category', ['only' => ['create', 'store']]);

        $this->middleware('role:edit_category', ['only' => ['update', 'edit']]);

        $this->middleware('role:delet_category', ['only' => ['destroy', 'bulkDelete']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin/categories/index');
    }

    /**
     * index categories - Process datatables ajax request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoriesData()
    {
        $categories = $this->em->createQueryBuilder()
            ->select([
                'c.id',
                'c.name',
                'c.createdAt',
                'u.id as userId',
                'u.name as userName',
            ])
            ->from(Category::class, 'c')
            ->innerJoin(User::class, 'u')
            ->getQuery()
            ->getResult();
//        $categories = Category::join('users', 'categories.user_id', '=', 'users.id')
//                        ->select(['categories.id', 'categories.name AS category_name', 'categories.user_id', 'users.name', 'categories.created_at']);

        return Datatables::of($categories)
                ->editColumn('category_name', function ($model) {
                    return $model['name'];
                })
                ->editColumn('created_at', function ($model) {
                    return "<abbr title='".$model['createdAt']->format('F d, Y @ h:i A')."'>".$model['createdAt']->format('F d, Y')."</abbr>";
                })
                ->editColumn('users.name', function ($model) {
                    return '<a href="'.route('users.show', $model['userId']).'" class="link">'.$model['userName'].' <i class="fas fa-external-link-alt"></i></a>';
                })
                ->addColumn('bulkAction', '<input type="checkbox" name="selected_ids[]" id="bulk_ids" value="{{ $id }}">')
                ->addColumn('actions', function ($model) {
                    return '
                     <div class="dropdown float-right">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-cog"></i> Action
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="'.route('categories.show', $model['id']).'"><i class="fas fa-eye"></i> View</a>
                            <a class="dropdown-item" href="'.route('categories.edit', $model['id']).'"><i class="fas fa-edit"></i> Edit</a>
                            <a class="dropdown-item text-danger" href="#" onclick="callDeletItem(\''.$model['id'].'\', \'categories\');"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>';
                })
                ->rawColumns(['actions','users.name','bulkAction','created_at'])
                ->make(true);
    }

    /**
     *  Select2 categories - Process select2 ajax request.
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
    */
    public function categoriesAjaxSelectData(Request $request)
    {
        if ($request->ajax()) {
            $page = $request->page;
            $resultCount = 10;

            $offset = ($page - 1) * $resultCount;

            $categories = $this->em->createQueryBuilder()
                ->select('c')
                ->from(Category::class, 'c')
                ->where('c.name LIKE :term')
                ->orderBy('c.name')
                ->setParameter('term', '%' . $request->term . '%')
                ->getQuery()
                ->getResult();
//            $categories = $this->em->getRepository(Category::class)->findBy(['name' => $request->term]);
//            $categories = Category::where('name', 'LIKE', '%' . $request->term. '%')
//                                ->orderBy('name')
//                                ->skip($offset)
//                                ->take($resultCount)
//                                ->selectRaw('id, name as text')
//                                ->get();

            $resultSelect2 = [];
            foreach ($categories as $category) {
                $resultSelect2[] = [
                    'id' => $category->id,
                    'text' => $category->name,
                ];
            }

            $count = count($resultSelect2);
//            $count = Count(Category::where('name', 'LIKE', '%' . $request->term. '%')
//                                ->orderBy('name')
//                                ->selectRaw('id, name as text')
//                                ->get());

            $endCount = $offset + $resultCount;
            $morePages = $count > $endCount;

            $results = [
                "results" => $resultSelect2,
                "pagination" => [
                    "more" => $morePages,
                ]
            ];
            return response()->json($results);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin/categories/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validations
        $validatedData = $request->validate([
            'name' => 'required|unique:categories|max:150',
        ]);

        // If validations fail
        if (!$validatedData) {
            return redirect()->back()
                    ->withErrors($validator)->withInput();
        }

        // Store the item
        $category = new Category;
        $category->name = $request->name;
        $category->user_id = Auth::user()->id;
        $category->save();

        // Back to index with success
        return redirect()->route('categories.index')->with('custom_success', 'Category has been added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = $this->em->getRepository(Category::class)->find($id);
//        $category = Category::findOrFail($id);
        return view('admin/categories/show', ['category' => $category]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category = $this->em->getRepository(Category::class)->find($id);
//        $category = Category::findOrFail($id);
        return view('admin/categories/edit', ['category' => $category]);
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
        // Validations
        $validatedData = $request->validate([
            'name' => 'required|max:150',
        ]);

        // If validations fail
        if (!$validatedData) {
            return redirect()->back()
                    ->withErrors($validator)->withInput();
        }

        // Update the item
        $category = Category::findOrFail($id);
        $category->name = $request->name;
        $category->save();

        // Back to index with success
        return redirect()->route('categories.index')->with('custom_success', 'Category has been updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Find the category by $id
        $category = Category::findOrFail($id);

        // Foreign Key Error Protection
        if ($category->blogs()->count() > 0) {
            return back()->with('custom_errors', 'Category was not deleted. It is already attached with some blogs.');
        }

        // permanent delete
        $status = $category->delete();

        if ($status) {
            // If success
            return back()->with('custom_success', 'Category has been deleted.');
        } else {
            // If no success
            return back()->with('custom_errors', 'Category was not deleted. Something went wrong.');
        }
    }

    /**
     * Bulk delete items in the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        $arrId = explode(",", $request->ids);

        // Foreign Key Error Protection
        $categories = Category::find($arrId);
        foreach ($categories as $category) {
            if ($category->blogs()->count() > 0) {
                return back()->with('custom_errors', '<b>'.$category->name. '</b>: It is already attached with some blogs. Categories were not deleted');
            }
        }

        // If no Foreign Key Error
        $status = Category::destroy($arrId);

        if ($status) {
            // If success
            return back()->with('custom_success', 'Bulk Delete action completed.');
        } else {
            // If no success
            return back()->with('custom_errors', 'Bulk Delete action failed. Something went wrong.');
        }
    }
}
