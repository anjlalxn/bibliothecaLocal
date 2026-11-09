<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportBooks extends Command
{
    protected $signature = 'books:import {--total=150}';
    protected $description = 'Import books from Open Library';

    public function handle()
    {
        $subjects = ['fiction', 'science', 'history', 'technology', 'romance', 'mystery', 'biography', 'fantasy'];
        $total = (int) $this->option('total');
        $perSubject = (int) ceil($total / count($subjects));
        $imported = 0;

        foreach ($subjects as $subject) {
            if ($imported >= $total) break;

            $response = Http::withHeaders([
                    'User-Agent' => 'Bibliotheca (bibliotheca_lib@gmail.com)', 
                ])
                ->timeout(15)
                ->retry(2, 1000)
                ->get('https://openlibrary.org/search.json', [
                    'q'      => 'subject:' . $subject,
                    'limit'  => $perSubject,
                    'fields' => 'title,author_name,publisher,first_publish_year',
                ]);

            if ($response->failed()) {
                $this->warn("Skipped {$subject}: request failed");
                continue;
            }

            $category = Category::firstOrCreate([
                'category_name' => ucfirst($subject),
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

        $this->info("Imported {$imported} books.");
    }
}