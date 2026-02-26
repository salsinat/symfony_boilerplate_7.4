<?php

namespace App\Tests\Service;

use App\Entity\Product;
use App\Service\CsvImportService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class CsvImportServiceTest extends TestCase
{
    public function testImportProductsIsSuccessful()
    {
        // Mock dependencies
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $slugger = $this->createMock(SluggerInterface::class);

        // Expect persist to be called
        $entityManager->expects($this->any())
            ->method('persist')
            ->with($this->isInstanceOf(Product::class));

        $entityManager->expects($this->atLeastOnce())
            ->method('flush');

        // Mock slugger
        $slugger->expects($this->any())
            ->method('slug')
            ->willReturn(new UnicodeString('product-slug'));

        $service = new CsvImportService($entityManager, $slugger);

        // Create a temporary CSV file
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test');
        $handle = fopen($tempFile, 'w');
        fputcsv($handle, ['Name', 'Price', 'Description', 'Stock']); // Header
        fputcsv($handle, ['Test Product', '1000', 'Description', '10']); // Data
        fclose($handle);

        $result = $service->importProducts($tempFile);

        // Clean up
        unlink($tempFile);

        $this->assertEquals(1, $result['imported']);
        $this->assertEmpty($result['errors']);
    }
}
