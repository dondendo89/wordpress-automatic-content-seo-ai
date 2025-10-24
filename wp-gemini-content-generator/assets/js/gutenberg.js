/**
 * WP Gemini Content Generator - Gutenberg Integration
 * Modern sidebar panel with AI tools
 */

(function() {
    'use strict';

    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { PanelBody, PanelRow, TextControl, Button, Spinner, Notice, SelectControl, ToggleControl, RangeControl } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    const { apiFetch } = wp;

    // AI Tools Panel Component
    function WGC_AI_Tools_Panel() {
        const [isGenerating, setIsGenerating] = useState(false);
        const [notice, setNotice] = useState(null);
        const [settings, setSettings] = useState({
            language: 'en',
            contentLength: 'long',
            emojiIcons: true,
            seoFocus: '',
            generateMeta: true,
            generateTags: true,
            generateExcerpt: true
        });

        const { getCurrentPost } = useSelect((select) => ({
            getCurrentPost: () => select('core/editor').getCurrentPost()
        }));

        const { editPost } = useDispatch('core/editor');

        const post = getCurrentPost();

        // Generate content using AI
        const generateContent = async (type) => {
            if (!post || !post.title) {
                setNotice({
                    type: 'error',
                    message: __('Please add a title to your post first.', 'wp-gemini-content-generator')
                });
                return;
            }

            setIsGenerating(true);
            setNotice(null);

            try {
                const response = await apiFetch({
                    path: '/wp-gemini-content-generator/v1/generate',
                    method: 'POST',
                    data: {
                        post_id: post.id,
                        type: type,
                        title: post.title,
                        content: post.content || '',
                        language: settings.language,
                        content_length: settings.contentLength,
                        emoji_icons: settings.emojiIcons,
                        seo_focus: settings.seoFocus,
                        generate_meta: settings.generateMeta,
                        generate_tags: settings.generateTags,
                        generate_excerpt: settings.generateExcerpt
                    }
                });

                if (response.success) {
                    // Update post content based on type
                    switch (type) {
                        case 'content':
                            editPost({ content: response.data.content });
                            break;
                        case 'meta_description':
                            editPost({ meta: { _wgc_meta_description: response.data.meta_description } });
                            break;
                        case 'tags':
                            editPost({ tags: response.data.tags });
                            break;
                        case 'excerpt':
                            editPost({ excerpt: response.data.excerpt });
                            break;
                        case 'all':
                            editPost({
                                content: response.data.content,
                                meta: { _wgc_meta_description: response.data.meta_description },
                                tags: response.data.tags,
                                excerpt: response.data.excerpt
                            });
                            break;
                    }

                    setNotice({
                        type: 'success',
                        message: response.data.message || __('Content generated successfully!', 'wp-gemini-content-generator')
                    });
                } else {
                    setNotice({
                        type: 'error',
                        message: response.data.message || __('Error generating content.', 'wp-gemini-content-generator')
                    });
                }
            } catch (error) {
                setNotice({
                    type: 'error',
                    message: __('Network error. Please try again.', 'wp-gemini-content-generator')
                });
            } finally {
                setIsGenerating(false);
            }
        };

        // Clear notice after 5 seconds
        useEffect(() => {
            if (notice) {
                const timer = setTimeout(() => setNotice(null), 5000);
                return () => clearTimeout(timer);
            }
        }, [notice]);

        return (
            <PanelBody
                title={__('🤖 AI Content Generator', 'wp-gemini-content-generator')}
                initialOpen={true}
                icon="admin-tools"
            >
                {notice && (
                    <Notice
                        status={notice.type}
                        isDismissible={false}
                        className="wgc-gutenberg-notice"
                    >
                        {notice.message}
                    </Notice>
                )}

                <PanelRow>
                    <div className="wgc-ai-controls">
                        {/* Main Generate All Button */}
                        <Button
                            isPrimary
                            isLarge
                            onClick={() => generateContent('all')}
                            disabled={isGenerating || !post?.title}
                            className="wgc-generate-all-btn"
                        >
                            {isGenerating ? (
                                <>
                                    <Spinner />
                                    {__('Generating...', 'wp-gemini-content-generator')}
                                </>
                            ) : (
                                <>
                                    🚀 {__('Generate All Content', 'wp-gemini-content-generator')}
                                </>
                            )}
                        </Button>

                        {/* Individual Controls */}
                        <div className="wgc-individual-controls">
                            <Button
                                onClick={() => generateContent('content')}
                                disabled={isGenerating || !post?.title}
                                variant="secondary"
                                className="wgc-btn-content"
                            >
                                📝 {__('Content', 'wp-gemini-content-generator')}
                            </Button>

                            <Button
                                onClick={() => generateContent('meta_description')}
                                disabled={isGenerating || !post?.title}
                                variant="secondary"
                                className="wgc-btn-meta"
                            >
                                🎯 {__('Meta Description', 'wp-gemini-content-generator')}
                            </Button>

                            <Button
                                onClick={() => generateContent('tags')}
                                disabled={isGenerating || !post?.title}
                                variant="secondary"
                                className="wgc-btn-tags"
                            >
                                🏷️ {__('Tags', 'wp-gemini-content-generator')}
                            </Button>

                            <Button
                                onClick={() => generateContent('excerpt')}
                                disabled={isGenerating || !post?.title}
                                variant="secondary"
                                className="wgc-btn-excerpt"
                            >
                                📄 {__('Excerpt', 'wp-gemini-content-generator')}
                            </Button>
                        </div>
                    </div>
                </PanelRow>

                {/* Settings Panel */}
                <PanelBody
                    title={__('⚙️ AI Settings', 'wp-gemini-content-generator')}
                    initialOpen={false}
                >
                    <PanelRow>
                        <SelectControl
                            label={__('Language', 'wp-gemini-content-generator')}
                            value={settings.language}
                            options={[
                                { label: __('English', 'wp-gemini-content-generator'), value: 'en' },
                                { label: __('Italian', 'wp-gemini-content-generator'), value: 'it' },
                                { label: __('Spanish', 'wp-gemini-content-generator'), value: 'es' },
                                { label: __('French', 'wp-gemini-content-generator'), value: 'fr' },
                                { label: __('German', 'wp-gemini-content-generator'), value: 'de' },
                                { label: __('Portuguese', 'wp-gemini-content-generator'), value: 'pt' },
                                { label: __('Russian', 'wp-gemini-content-generator'), value: 'ru' },
                                { label: __('Japanese', 'wp-gemini-content-generator'), value: 'ja' },
                                { label: __('Korean', 'wp-gemini-content-generator'), value: 'ko' },
                                { label: __('Chinese', 'wp-gemini-content-generator'), value: 'zh' },
                                { label: __('Arabic', 'wp-gemini-content-generator'), value: 'ar' },
                                { label: __('Hindi', 'wp-gemini-content-generator'), value: 'hi' }
                            ]}
                            onChange={(value) => setSettings({ ...settings, language: value })}
                        />
                    </PanelRow>

                    <PanelRow>
                        <SelectControl
                            label={__('Content Length', 'wp-gemini-content-generator')}
                            value={settings.contentLength}
                            options={[
                                { label: __('Short (500-800 words)', 'wp-gemini-content-generator'), value: 'short' },
                                { label: __('Medium (800-1500 words)', 'wp-gemini-content-generator'), value: 'medium' },
                                { label: __('Long (1500-3000 words)', 'wp-gemini-content-generator'), value: 'long' },
                                { label: __('Very Long (3000+ words)', 'wp-gemini-content-generator'), value: 'very_long' }
                            ]}
                            onChange={(value) => setSettings({ ...settings, contentLength: value })}
                        />
                    </PanelRow>

                    <PanelRow>
                        <TextControl
                            label={__('SEO Focus Keywords', 'wp-gemini-content-generator')}
                            value={settings.seoFocus}
                            onChange={(value) => setSettings({ ...settings, seoFocus: value })}
                            help={__('Enter keywords separated by commas', 'wp-gemini-content-generator')}
                        />
                    </PanelRow>

                    <PanelRow>
                        <ToggleControl
                            label={__('Include Emojis & Icons', 'wp-gemini-content-generator')}
                            checked={settings.emojiIcons}
                            onChange={(value) => setSettings({ ...settings, emojiIcons: value })}
                        />
                    </PanelRow>

                    <PanelRow>
                        <ToggleControl
                            label={__('Generate Meta Description', 'wp-gemini-content-generator')}
                            checked={settings.generateMeta}
                            onChange={(value) => setSettings({ ...settings, generateMeta: value })}
                        />
                    </PanelRow>

                    <PanelRow>
                        <ToggleControl
                            label={__('Generate Tags', 'wp-gemini-content-generator')}
                            checked={settings.generateTags}
                            onChange={(value) => setSettings({ ...settings, generateTags: value })}
                        />
                    </PanelRow>

                    <PanelRow>
                        <ToggleControl
                            label={__('Generate Excerpt', 'wp-gemini-content-generator')}
                            checked={settings.generateExcerpt}
                            onChange={(value) => setSettings({ ...settings, generateExcerpt: value })}
                        />
                    </PanelRow>
                </PanelBody>

                {/* Quick Actions */}
                <PanelBody
                    title={__('⚡ Quick Actions', 'wp-gemini-content-generator')}
                    initialOpen={false}
                >
                    <PanelRow>
                        <div className="wgc-quick-actions">
                            <Button
                                onClick={() => generateContent('content')}
                                disabled={isGenerating || !post?.title}
                                variant="secondary"
                                isSmall
                            >
                                ✨ {__('Expand Paragraph', 'wp-gemini-content-generator')}
                            </Button>

                            <Button
                                onClick={() => generateContent('meta_description')}
                                disabled={isGenerating || !post?.title}
                                variant="secondary"
                                isSmall
                            >
                                🔄 {__('Rephrase Content', 'wp-gemini-content-generator')}
                            </Button>

                            <Button
                                onClick={() => generateContent('tags')}
                                disabled={isGenerating || !post?.title}
                                variant="secondary"
                                isSmall
                            >
                                📊 {__('SEO Analysis', 'wp-gemini-content-generator')}
                            </Button>
                        </div>
                    </PanelRow>
                </PanelBody>
            </PanelBody>
        );
    }

    // Register the sidebar plugin
    registerPlugin('wp-gemini-content-generator', {
        render: () => (
            <>
                <PluginSidebarMoreMenuItem
                    target="wp-gemini-content-generator-sidebar"
                    icon="admin-tools"
                >
                    {__('AI Content Generator', 'wp-gemini-content-generator')}
                </PluginSidebarMoreMenuItem>
                <PluginSidebar
                    name="wp-gemini-content-generator-sidebar"
                    title={__('AI Content Generator', 'wp-gemini-content-generator')}
                    icon="admin-tools"
                >
                    <WGC_AI_Tools_Panel />
                </PluginSidebar>
            </>
        ),
    });

})();
