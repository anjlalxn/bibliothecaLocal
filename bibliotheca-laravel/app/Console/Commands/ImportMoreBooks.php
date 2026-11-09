<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportMoreBooks extends Command
{
    protected $signature = 'books:import-more {--total=50}';
    protected $description = 'Import more books (general and educational) from Open Library';

    public function handle()
    {
        // Open Library subject => category name saved in your database
        $subjects = [
            'psychology'             => 'Psychology',
            'art'                    => 'Art',
            'programming'            => 'Programming',
            'comics'                 => 'Comics',
            'information technology' => 'Information Technology',
            'nursing'                => 'Nursing',
            'education'              => 'Education',
            'engineering'            => 'Engineering',
        ];

        $total = (int) $this->option('total');
        $perSubject = (int) ceil($total / count($subjects)); 
        $imported = 0;

        foreach ($subjects as $subject => $categoryName) {
            if ($imported >= $total) break;

            $response = Http::withHeaders([
                    'User-Agent' => 'Bibliotheca (bibliotheca_lib@gmail.com)', 
                ])
                ->timeout(15)
                ->retry(2, 1000)
                ->get('https://openlibrary.org/search.json', [
                    'q'      => 'subject:"' . $subject . '"',
                    'limit'  => 30, 
                    'fields' => 'title,author_name,publisher,first_publish_year',
                ]);

            if ($response->failed()) {
                $this->warn("Skipped {$subject}: request failed");
                continue;
            }

            $category = Category::firstOrCreate([
                'category_name' => $categoryName,
            ]);

            $importedThisSubject = 0;

            foreach ($response->json('docs', []) as $doc) {
                if ($imported >= $total || $importedThisSubject >= $perSubject) break;
                if (empty($doc['title'])) continue;

                $quantity = rand(1, 5); // fewer copies per book

                $book = Book::firstOrCreate(
                    [
                        'title'  => substr($doc['title'], 0, 255),
                        'author' => substr($doc['author_name'][0] ?? 'Unknown', 0, 255),
                    ],
                    [
                        'category_id'        => $category->category_id,
                        'publisher'          => substr($doc['publisher'][0] ?? 'Unknown', 0, 255),
                        'publication_year'   => $doc['first_publish_year'] ?? null,
                        'quantity'           => $quantity,
                        'available_quantity' => $quantity,
                        'status'             => 'available', 
                    ]
                );

                if ($book->wasRecentlyCreated) {
                    $imported++;
                    $importedThisSubject++;
                }
            }

            $this->info("Done: {$subject} ({$importedThisSubject} new)");
            sleep(2); 
        }

        $this->info("Imported {$imported} books.");
    }
}