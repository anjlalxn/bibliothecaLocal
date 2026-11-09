<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportMangaComicsBooks extends Command
{
    protected $signature = 'books:import-manga-comics';
    protected $description = 'Import manga, comics, and more fiction and educational books from Open Library';

    public function handle()
    {
        // Open Library subject => [category name saved in your database, how many new books to add]
        $subjects = [
            // Manga and comics
            'manga'              => ['Manga', 20],
            'graphic novels'     => ['Comics', 10],

            // Fiction (15)
            'science fiction'    => ['Science Fiction', 5],
            'horror'             => ['Horror', 4],
            'thriller'           => ['Thriller', 3],
            'historical fiction' => ['Historical Fiction', 3],

            // Educational (15)
            'mathematics'        => ['Mathematics', 4],
            'physics'            => ['Physics', 4],
            'biology'            => ['Biology', 4],
            'chemistry'          => ['Chemistry', 3],
        ];

        $imported = 0;

        foreach ($subjects as $subject => [$categoryName, $target]) {
            $response = Http::withHeaders([
                    'User-Agent' => 'Bibliotheca (bibliotheca_lib@gmail.com)',
                ])
                ->timeout(15)
                ->retry(2, 1000)
                ->get('https://openlibrary.org/search.json', [
                    'q'        => 'subject:"' . $subject . '"',
                    'language' => 'eng', // English results only
                    'limit'    => 50,    // extra, since duplicates get skipped
                    'fields'   => 'title,author_name,publisher,first_publish_year',
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
                if ($importedThisSubject >= $target) break;
                if (empty($doc['title'])) continue;

                $quantity = rand(1, 3); // fewer copies per book

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

            $this->info("Done: {$subject} ({$importedThisSubject} of {$target} new)");
            sleep(2); 
        }

        $this->info("Imported {$imported} books.");
    }
}