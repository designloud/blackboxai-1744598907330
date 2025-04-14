<?php

namespace VendorName\Conversa\Tests\Unit;

use VendorName\Conversa\Tests\TestCase;
use VendorName\Conversa\Models\ConversaAttachment;
use VendorName\Conversa\Models\ConversaMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_create_an_attachment()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Message with attachment',
            'type' => 'file',
        ]);

        $attachment = ConversaAttachment::create([
            'message_id' => $message->id,
            'file_name' => 'test.pdf',
            'file_path' => 'conversa/attachments/test.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertDatabaseHas('conversa_attachments', [
            'message_id' => $message->id,
            'file_name' => 'test.pdf',
        ]);
    }

    /** @test */
    public function it_can_store_a_file()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Message with attachment',
            'type' => 'file',
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $attachment = ConversaAttachment::storeFile($file, $message->id);

        Storage::disk(config('conversa.attachments.storage_disk'))
            ->assertExists($attachment->file_path);

        $this->assertDatabaseHas('conversa_attachments', [
            'message_id' => $message->id,
            'file_name' => 'document.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'mime_type' => $file->getMimeType(),
        ]);
    }

    /** @test */
    public function it_formats_file_size()
    {
        $attachment = ConversaAttachment::create([
            'message_id' => 1,
            'file_name' => 'test.pdf',
            'file_path' => 'test.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024 * 1024, // 1MB
            'mime_type' => 'application/pdf',
        ]);

        $this->assertEquals('1 MB', $attachment->formatted_size);
    }

    /** @test */
    public function it_determines_if_file_is_image()
    {
        $imageAttachment = ConversaAttachment::create([
            'message_id' => 1,
            'file_name' => 'image.jpg',
            'file_path' => 'image.jpg',
            'file_type' => 'jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
        ]);

        $pdfAttachment = ConversaAttachment::create([
            'message_id' => 1,
            'file_name' => 'document.pdf',
            'file_path' => 'document.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertTrue($imageAttachment->is_image);
        $this->assertFalse($pdfAttachment->is_image);
    }

    /** @test */
    public function it_gets_correct_icon_class()
    {
        $attachments = [
            ['mime_type' => 'image/jpeg', 'expected' => 'fa-image'],
            ['mime_type' => 'video/mp4', 'expected' => 'fa-video'],
            ['mime_type' => 'audio/mpeg', 'expected' => 'fa-music'],
            ['mime_type' => 'application/pdf', 'expected' => 'fa-file-pdf'],
            ['mime_type' => 'application/msword', 'expected' => 'fa-file-word'],
            ['mime_type' => 'application/vnd.ms-excel', 'expected' => 'fa-file-excel'],
            ['mime_type' => 'application/zip', 'expected' => 'fa-file-archive'],
            ['mime_type' => 'text/plain', 'expected' => 'fa-file'],
        ];

        foreach ($attachments as $data) {
            $attachment = ConversaAttachment::create([
                'message_id' => 1,
                'file_name' => 'test',
                'file_path' => 'test',
                'file_type' => 'test',
                'file_size' => 1024,
                'mime_type' => $data['mime_type'],
            ]);

            $this->assertEquals($data['expected'], $attachment->getIconClass());
        }
    }

    /** @test */
    public function it_validates_allowed_file_types()
    {
        config(['conversa.attachments.allowed_types' => ['image/jpeg', 'application/pdf']]);

        $this->assertTrue(ConversaAttachment::isAllowedFileType('image/jpeg'));
        $this->assertTrue(ConversaAttachment::isAllowedFileType('application/pdf'));
        $this->assertFalse(ConversaAttachment::isAllowedFileType('application/exe'));
    }

    /** @test */
    public function it_validates_file_size()
    {
        config(['conversa.attachments.max_size' => 1024]); // 1MB in KB

        $this->assertTrue(ConversaAttachment::isAllowedFileSize(1024 * 1024)); // 1MB in bytes
        $this->assertFalse(ConversaAttachment::isAllowedFileSize(2048 * 1024)); // 2MB in bytes
    }

    /** @test */
    public function it_validates_file()
    {
        config([
            'conversa.attachments.allowed_types' => ['image/jpeg'],
            'conversa.attachments.max_size' => 1024, // 1MB in KB
        ]);

        $validFile = UploadedFile::fake()->image('photo.jpg')->size(512); // 512KB
        $invalidTypeFile = UploadedFile::fake()->create('document.pdf', 512);
        $oversizedFile = UploadedFile::fake()->image('large.jpg')->size(2048); // 2MB

        $this->assertEmpty(ConversaAttachment::validateFile($validFile));
        $this->assertNotEmpty(ConversaAttachment::validateFile($invalidTypeFile));
        $this->assertNotEmpty(ConversaAttachment::validateFile($oversizedFile));
    }

    /** @test */
    public function it_deletes_file_from_storage_when_model_is_deleted()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024);
        
        $attachment = ConversaAttachment::create([
            'message_id' => 1,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $file->store('conversa/attachments', 'public'),
            'file_type' => 'pdf',
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        Storage::disk('public')->assertExists($attachment->file_path);
        
        $attachment->delete();
        
        Storage::disk('public')->assertMissing($attachment->file_path);
    }
}
