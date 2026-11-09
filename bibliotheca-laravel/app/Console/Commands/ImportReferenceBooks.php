<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportReferenceBooks extends Command
{
    protected $signature = 'books:import-reference {--total=86}';
    protected $description = 'Import reference books (encyclopedias, dictionaries, etc.) from Open Library';

    public function handle()
    {
        // Open Library subject => category name saved in your database
        $subjects = [
            'encyclopedias'        => 'Encyclopedias',
            'dictionaries'         => 'Dictionaries',
            'atlases'              => 'Atlases',
            'almanacs'             => 'Almanacs',
            'thesauruses'          => 'Thesauruses',
            'handbooks'            => 'Handbooks',
            'quotations'           => 'Quotations',
            'tagalog dictionaries' => 'Tagalog Dictionaries',
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
                    'limit'  => 40,
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

                $quantity = rand(1, 5); 

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