<?php

namespace VendorName\Conversa\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VendorName\Conversa\Models\ConversaAttachment;
use VendorName\Conversa\Models\ConversaMessage;

class ConversaAttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversaAttachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = [
            'image' => [
                'extensions' => ['jpg', 'png', 'gif'],
                'mimes' => ['image/jpeg', 'image/png', 'image/gif'],
            ],
            'document' => [
                'extensions' => ['pdf', 'doc', 'docx'],
                'mimes' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ],
            'spreadsheet' => [
                'extensions' => ['xls', 'xlsx'],
                'mimes' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ],
        ];

        $fileType = $this->faker->randomElement(array_keys($fileTypes));
        $extension = $this->faker->randomElement($fileTypes[$fileType]['extensions']);
        $mimeType = $fileTypes[$fileType]['mimes'][array_search($extension, $fileTypes[$fileType]['extensions'])];

        $fileName = $this->faker->words(3, true) . '.' . $extension;

        return [
            'message_id' => ConversaMessage::factory(),
            'file_name' => $fileName,
            'file_path' => 'conversa/attachments/' . $fileName,
            'file_type' => $extension,
            'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'mime_type' => $mimeType,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the attachment is an image.
     */
    public function image(): Factory
    {
        return $this->state(function (array $attributes) {
            $extension = $this->faker->randomElement(['jpg', 'png', 'gif']);
            $mimeType = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
            $fileName = $this->faker->words(3, true) . '.' . $extension;

            return [
                'file_name' => $fileName,
                'file_path' => 'conversa/attachments/' . $fileName,
                'file_type' => $extension,
                'mime_type' => $mimeType,
                'metadata' => [
                    'dimensions' => [
                        'width' => $this->faker->numberBetween(800, 1920),
                        'height' => $this->faker->numberBetween(600, 1080),
                    ],
                ],
            ];
        });
    }

    /**
     * Indicate that the attachment is a document.
     */
    public function document(): Factory
    {
        return $this->state(function (array $attributes) {
            $extension = $this->faker->randomElement(['pdf', 'doc', 'docx']);
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];
            $fileName = $this->faker->words(3, true) . '.' . $extension;

            return [
                'file_name' => $fileName,
                'file_path' => 'conversa/attachments/' . $fileName,
                'file_type' => $extension,
                'mime_type' => $mimeTypes[$extension],
            ];
        });
    }

    /**
     * Set the file size for the attachment.
     */
    public function size(int $sizeInBytes): Factory
    {
        return $this->state(function (array $attributes) use ($sizeInBytes) {
            return [
                'file_size' => $sizeInBytes,
            ];
        });
    }

    /**
     * Add metadata to the attachment.
     */
    public function withMetadata(array $metadata): Factory
    {
        return $this->state(function (array $attributes) use ($metadata) {
            return [
                'metadata' => array_merge($attributes['metadata'] ?? [], $metadata),
            ];
        });
    }

    /**
     * Indicate that the attachment belongs to a specific message.
     */
    public function forMessage(ConversaMessage $message): Factory
    {
        return $this->state(function (array $attributes) use ($message) {
            return [
                'message_id' => $message->id,
            ];
        });
    }
}
