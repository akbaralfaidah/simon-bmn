<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Bast;
use App\Models\LoanRequest;
use App\Models\User;
use App\Services\WordTemplateService;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class BastWordDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_bast_document_is_valid_docx_with_well_formed_xml(): void
    {
        $user = User::factory()->create(['name' => 'Budi Pratama & Rekan <Staff>']);
        $asset = Asset::create([
            'name' => 'Laptop ThinkPad & Pro <T480>',
            'condition' => 'Baik',
            'nup' => '0001',
            'item_code' => '3.05.01.04.001',
            'brand_type' => 'Lenovo & IBM',
            'satker_code' => '029.05.01.683416',
        ]);

        $loan = LoanRequest::create([
            'user_id' => $user->id,
            'purpose' => 'Kegiatan patroli lapangan & inspeksi BMN <Gakkum>',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'status' => 'approved',
        ]);

        $bast = Bast::create([
            'bast_number' => 'BAST-2026-000001',
            'bast_type' => 'loan',
            'reference_type' => LoanRequest::class,
            'reference_id' => $loan->id,
            'issued_by' => $user->id,
            'received_by' => $user->id,
            'status' => 'draft',
            'snapshot' => [
                'items' => [
                    [
                        'id' => $asset->id,
                        'name' => $asset->name,
                        'item_code' => $asset->item_code,
                        'nup' => $asset->nup,
                        'brand_type' => $asset->brand_type,
                        'condition' => $asset->condition,
                    ],
                ],
                'purpose' => $loan->purpose,
            ],
        ]);

        $service = app(WordTemplateService::class);
        $outputPath = $service->generateBastDocument($bast);

        $this->assertFileExists($outputPath);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($outputPath) === true, 'Generated document must be a valid zip archive');

        $documentXml = $zip->getFromName('word/document.xml');
        $this->assertNotEmpty($documentXml);

        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $loaded = $dom->loadXML($documentXml);

        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();

        $this->assertTrue($loaded, 'word/document.xml must be well-formed XML: '.json_encode($xmlErrors));
        $this->assertEmpty($xmlErrors, 'There must be no XML parsing errors in word/document.xml');

        $this->assertStringContainsString('w:code="9"', $documentXml, 'Document must specify A4 paper code');
        $this->assertStringContainsString('w:w="11906"', $documentXml, 'Document must specify A4 width');
        $this->assertStringContainsString('w:h="16838"', $documentXml, 'Document must specify A4 height');
        $this->assertStringNotContainsString('(Budi Pratama', $documentXml, 'Names must not have parentheses');
        $this->assertStringNotContainsString('( Budi Pratama', $documentXml, 'Names must not have parentheses');

        $zip->close();
        @unlink($outputPath);
    }
}
