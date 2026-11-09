<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportFilipinoBooks extends Command
{
    protected $signature = 'books:import-filipino {--total=120}';
    protected $description = 'Import Filipino-related books from Open Library';

    public function handle()
    {
        // Open Library subject => category name saved in your database
        $subjects = [
            'filipino literature'                 => 'Filipino Literature',
            'philippines history'                 => 'Philippine History',
            'philippines fiction'                 => 'Philippine Fiction',
            'philippines folklore'                => 'Philippine Folklore',
            'philippines politics and government' => 'Philippine Politics',
            'philippines social life and customs' => 'Philippine Culture',
            'philippines economic conditions'     => 'Philippine Economy',
            'philippines biography'               => 'Philippine Biography',
        ];

        $total = (int) $this->option('total');
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

            foreach ($response->json('docs', []) as $doc) {
                if ($imported >= $total) break;
                if (empty($doc['title'])) continue;

                $quantity = rand(3, 20); 

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

                if ($book->wasRecentlyCreated) $imported++;
            }

            $this->info("Done: {$subject}");
            sleep(2); 
        }

        $this->info("Imported {$imported} Filipino books.");
    }
}