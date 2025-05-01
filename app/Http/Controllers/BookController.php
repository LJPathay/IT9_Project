<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the books.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // In a real application, you would fetch books from the database
        // For now, we'll use sample data
        $books = [
            [
                'id' => 1,
                'title' => 'The Midnight Library',
                'author' => 'Matt Haig',
                'status' => 'available',
                'cover' => null
            ],
            [
                'id' => 2,
                'title' => 'Klara and the Sun',
                'author' => 'Kazuo Ishiguro',
                'status' => 'borrowed',
                'cover' => null
            ],
            [
                'id' => 3,
                'title' => 'Project Hail Mary',
                'author' => 'Andy Weir',
                'status' => 'available',
                'cover' => null
            ],
            [
                'id' => 4,
                'title' => 'The Four Winds',
                'author' => 'Kristin Hannah',
                'status' => 'available',
                'cover' => null
            ],
            [
                'id' => 5,
                'title' => 'The Great Gatsby',
                'author' => 'F. Scott Fitzgerald',
                'status' => 'borrowed',
                'cover' => null
            ],
            [
                'id' => 6,
                'title' => 'To Kill a Mockingbird',
                'author' => 'Harper Lee',
                'status' => 'available',
                'cover' => null
            ],
            [
                'id' => 7,
                'title' => '1984',
                'author' => 'George Orwell',
                'status' => 'available',
                'cover' => null
            ],
            [
                'id' => 8,
                'title' => 'The Alchemist',
                'author' => 'Paulo Coelho',
                'status' => 'available',
                'cover' => null
            ]
        ];

        return view('books.index', compact('books'));
    }

    /**
     * Display the specified book.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // In a real application, you would fetch the book from the database
        // For now, we'll use sample data
        $book = [
            'id' => $id,
            'title' => 'Sample Book Title',
            'author' => 'Sample Author',
            'description' => 'This is a sample book description that would come from the database.',
            'publication_year' => '2023',
            'genre' => 'Fiction',
            'isbn' => '978-3-16-148410-0',
            'status' => 'available'
        ];

        return view('books.show', compact('book'));
    }
}