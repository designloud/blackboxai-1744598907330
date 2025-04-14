<?php

namespace SwellSystems\Conversa\Services;

use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Mention\MentionExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\Extension\Attributes\AttributesExtension;

class MessageFormatter
{
    /**
     * The Markdown converter instance.
     *
     * @var MarkdownConverter
     */
    protected $converter;

    /**
     * URL preview cache.
     *
     * @var array
     */
    protected $urlPreviews = [];

    /**
     * Create a new message formatter instance.
     */
    public function __construct()
    {
        $config = [
            'mentions' => [
                'user' => [
                    'prefix' => '@',
                    'pattern' => '[a-zA-Z0-9_]{3,}',
                    'generator' => [$this, 'generateUserMention'],
                ],
                'channel' => [
                    'prefix' => '#',
                    'pattern' => '[a-zA-Z0-9_-]{2,}',
                    'generator' => [$this, 'generateChannelMention'],
                ],
            ],
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
            'commonmark' => [
                'enable_em' => true,
                'enable_strong' => true,
                'use_asterisk' => true,
                'use_underscore' => true,
                'unordered_list_markers' => ['-', '*', '+'],
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new MentionExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new TaskListExtension());
        $environment->addExtension(new AttributesExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Format a message with Markdown, mentions, and emoji.
     *
     * @param string $content
     * @return HtmlString
     */
    public function format(string $content): HtmlString
    {
        // Convert emoji shortcodes
        $content = $this->convertEmoji($content);

        // Parse Markdown and mentions
        $html = $this->converter->convert($content);

        // Extract and process URLs for previews
        $html = $this->processUrlPreviews($html);

        return new HtmlString($html);
    }

    /**
     * Convert emoji shortcodes to Unicode characters.
     *
     * @param string $content
     * @return string
     */
    protected function convertEmoji(string $content): string
    {
        return preg_replace_callback('/:([\w+-]+):/', function ($matches) {
            return $this->getEmojiCharacter($matches[1]) ?? $matches[0];
        }, $content);
    }

    /**
     * Get emoji character from shortcode.
     *
     * @param string $shortcode
     * @return string|null
     */
    protected function getEmojiCharacter(string $shortcode): ?string
    {
        // Load emoji map from config or cache
        $emojiMap = config('conversa.emoji_map', []);
        return $emojiMap[$shortcode] ?? null;
    }

    /**
     * Process URLs for link previews.
     *
     * @param string $html
     * @return string
     */
    protected function processUrlPreviews(string $html): string
    {
        return preg_replace_callback('/<a href="([^"]+)"[^>]*>([^<]+)<\/a>/', function ($matches) {
            $url = $matches[1];
            $preview = $this->getUrlPreview($url);

            if ($preview) {
                return $matches[0] . $this->renderUrlPreview($preview);
            }

            return $matches[0];
        }, $html);
    }

    /**
     * Get URL preview metadata.
     *
     * @param string $url
     * @return array|null
     */
    protected function getUrlPreview(string $url): ?array
    {
        if (isset($this->urlPreviews[$url])) {
            return $this->urlPreviews[$url];
        }

        try {
            $metadata = [
                'url' => $url,
                'title' => null,
                'description' => null,
                'image' => null,
                'site_name' => null,
            ];

            // Use OpenGraph/Twitter Card parsers or similar libraries
            // to extract metadata from the URL

            $this->urlPreviews[$url] = $metadata;
            return $metadata;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Render URL preview HTML.
     *
     * @param array $preview
     * @return string
     */
    protected function renderUrlPreview(array $preview): string
    {
        return view('conversa::partials.url-preview', $preview)->render();
    }

    /**
     * Generate user mention HTML.
     *
     * @param string $username
     * @return string
     */
    public function generateUserMention(string $username): string
    {
        $user = \App\Models\User::where('username', $username)->first();
        
        if (!$user) {
            return '@' . $username;
        }

        return sprintf(
            '<span class="mention user-mention" data-user-id="%s">@%s</span>',
            $user->id,
            $username
        );
    }

    /**
     * Generate channel mention HTML.
     *
     * @param string $channelName
     * @return string
     */
    public function generateChannelMention(string $channelName): string
    {
        $space = \SwellSystems\Conversa\Models\ConversaSpace::where('name', $channelName)->first();
        
        if (!$space) {
            return '#' . $channelName;
        }

        return sprintf(
            '<span class="mention channel-mention" data-channel-id="%s">#%s</span>',
            $space->id,
            $channelName
        );
    }

    /**
     * Extract mentions from content.
     *
     * @param string $content
     * @return array
     */
    public function extractMentions(string $content): array
    {
        $mentions = [
            'users' => [],
            'channels' => [],
        ];

        // Extract user mentions
        preg_match_all('/@([a-zA-Z0-9_]{3,})/', $content, $userMatches);
        if (!empty($userMatches[1])) {
            $mentions['users'] = \App\Models\User::whereIn('username', $userMatches[1])->get();
        }

        // Extract channel mentions
        preg_match_all('/#([a-zA-Z0-9_-]{2,})/', $content, $channelMatches);
        if (!empty($channelMatches[1])) {
            $mentions['channels'] = \SwellSystems\Conversa\Models\ConversaSpace::whereIn('name', $channelMatches[1])->get();
        }

        return $mentions;
    }

    /**
     * Clean and sanitize message content.
     *
     * @param string $content
     * @return string
     */
    public function sanitize(string $content): string
    {
        // Remove potentially harmful content
        $content = strip_tags($content);
        
        // Limit message length
        $maxLength = config('conversa.max_message_length', 10000);
        if (Str::length($content) > $maxLength) {
            $content = Str::limit($content, $maxLength);
        }

        return $content;
    }
}
