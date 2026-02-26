<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CsvImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger
    ) {
    }

    public function importProducts(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }

        if (($handle = fopen($filePath, "r")) === false) {
            throw new \Exception("Could not open file: $filePath");
        }

        $headers = fgetcsv($handle, 1000, ","); // Skip header row
        $importedCount = 0;
        $errors = [];

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            // Expected format: Name, Price (cents), Description, Stock
            // Modify index access based on your CSV structure. 
            // Assuming: 0: Name, 1: Price, 2: Description, 3: Stock

            if (count($data) < 4) {
                $errors[] = "Row invalid: " . implode(',', $data);
                continue;
            }

            try {
                $name = $data[0];
                $price = (int) $data[1];
                $description = $data[2];
                $stock = (int) $data[3];

                $product = new Product();
                $product->setName($name);
                $product->setSlug(strtolower($this->slugger->slug($name)));
                $product->setPrice($price);
                $product->setDescription($description);
                $product->setStock($stock);
                $product->setType('physical'); // Default to physical for CSV imports
                $product->setWeight(1.0); // Default weight if missing

                $this->entityManager->persist($product);
                $importedCount++;

                // Flush in batches to manage memory
                if ($importedCount % 20 === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }

            } catch (\Exception $e) {
                $errors[] = "Error processing row " . $name . ": " . $e->getMessage();
            }
        }

        $this->entityManager->flush();
        fclose($handle);

        return [
            'imported' => $importedCount,
            'errors' => $errors
        ];
    }
}
