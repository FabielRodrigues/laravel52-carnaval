<?php

namespace CodePub\Http\Controllers\Admin;

use CodePub\Events\GenerateBook;
use CodePub\Models\Book;
use CodePub\Models\Category;
use CodePub\Services\BookService;
use Illuminate\Http\Request;

use CodePub\Http\Requests;
use CodePub\Http\Controllers\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

class BooksController extends Controller
{
    public function index()
    {
        if (auth()->user()->can('book_manage_all')) {
            $books = Book::all();
        } else {
            $books = Book::whereUserId(auth()->user()->id)->get();
        }

        return view('admin.books.index', compact('books'));
    }

    public function create()
    {
        $categories = Category::lists('name', 'id');
        return view('admin.books.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $input['user_id'] = auth()->user()->id;
        Book::create($input);
        return redirect()->route('admin.books.index');
    }

    public function edit($id)
    {
        $book = Book::find($id);

        if (Gate::denies('manage', $book)) {
            abort(403, 'You dont own this book');
        }

        $categories = Category::lists('name', 'id');
        return view('admin.books.edit', compact('book', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $book = Book::find($id);

        if (Gate::denies('manage', $book)) {
            abort(403, 'You dont own this book');
        }

        $book->update($request->all());
        return redirect()->route('admin.books.index');
    }

    public function destroy($id)
    {
        $book = Book::find($id);

        if (Gate::denies('manage', $book)) {
            abort(403, 'You dont own this book');
        }

        $book->delete();
        return redirect()->route('admin.books.index');
    }

    public function cover($id)
    {
        $book = Book::find($id);

        if (Gate::denies('manage', $book)) {
            abort(403, 'You dont own this book');
        }

        return view('admin.books.cover', compact('book'));
    }

    public function coverStore(Request $request, $id)
    {
        $book = Book::find($id);

        if (Gate::denies('manage', $book)) {
            abort(403, 'You dont own this book');
        }

        $bookService = app()->make(BookService::class);
        $bookService->storeCover($book, $request->file('file'));

        return redirect()->back();
    }

    public function export($id)
    {
        $book = Book::find($id);

        if (Gate::denies('manage', $book)) {
            abort(403, 'You dont own this book');
        }

        Event::fire(new GenerateBook($book));

        return redirect()->route('admin.books.index');
    }

    public function download($id)
    {
        $book = Book::find($id);

        if (Gate::denies('manage', $book)) {
            abort(403, 'You dont own this book');
        }

        if(file_exists(storage_path("books/{$book->id}/book.zip"))) {
            return response()->download(storage_path("books/{$book->id}/book.zip"));
        }

        return redirect()->route('admin.books.index');
    }
}
