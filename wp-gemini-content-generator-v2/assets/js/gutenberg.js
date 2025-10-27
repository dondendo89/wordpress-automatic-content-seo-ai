/**
 * Gutenberg integration JavaScript
 *
 * @package WP_Gemini_Content_Generator
 * @since 2.0.0
 */

(function() {
    'use strict';

    const { registerPlugin } = wp.plugins;
    const { PluginSidebar } = wp.editPost;
    const { PanelBody, Button, Spinner } = wp.components;
    const { useState } = wp.element;
    const { useSelect, useDispatch } = wp.data;
    const { apiFetch } = wp.apiFetch;

    function WGCGutenbergPanel() {
        const [status, setStatus] = useState(null);
        const [isLoading, setIsLoading] = useState(false);

        const { editPost } = useDispatch('core/editor');
        const postId = useSelect((select) => select('core/editor').getCurrentPostId());

        const generateContent = async (type) => {
            setIsLoading(true);
            setStatus({ type: 'loading', message: 'Generating...' });

            try {
                const response = await apiFetch({
                    path: '/wp-gemini-content-generator/v1/generate',
                    method: 'POST',
                    data: {
                        postId: postId,
                        type: type
                    }
                });

                if (response.success) {
                    setStatus({ type: 'success', message: 'Generated successfully!' });
                    
                    // Update editor content
                    if (type === 'content' && response.content) {
                        editPost({ content: response.content });
                    } else if (type === 'excerpt' && response.excerpt) {
                        editPost({ excerpt: response.excerpt });
                    }
                } else {
                    setStatus({ type: 'error', message: 'Generation failed' });
                }
            } catch (error) {
                setStatus({ type: 'error', message: 'Network error occurred' });
            } finally {
                setIsLoading(false);
            }
        };

        return (
            <PanelBody title="AI Content Generator" initialOpen={true}>
                <div className="wgc-gutenberg-panel">
                    <div className="wgc-gutenberg-buttons">
                        <Button
                            variant="secondary"
                            onClick={() => generateContent('content')}
                            disabled={isLoading}
                        >
                            Generate Content
                        </Button>
                        
                        <Button
                            variant="secondary"
                            onClick={() => generateContent('meta')}
                            disabled={isLoading}
                        >
                            Generate Meta Description
                        </Button>
                        
                        <Button
                            variant="secondary"
                            onClick={() => generateContent('tags')}
                            disabled={isLoading}
                        >
                            Generate Tags
                        </Button>
                        
                        <Button
                            variant="secondary"
                            onClick={() => generateContent('excerpt')}
                            disabled={isLoading}
                        >
                            Generate Excerpt
                        </Button>
                    </div>

                    {status && (
                        <div className={`wgc-gutenberg-status ${status.type}`}>
                            {isLoading && <Spinner />}
                            {status.message}
                        </div>
                    )}
                </div>
            </PanelBody>
        );
    }

    registerPlugin('wp-gemini-content-generator', {
        render: () => (
            <PluginSidebar
                name="wp-gemini-content-generator"
                title="AI Content Generator"
                icon="admin-tools"
            >
                <WGCGutenbergPanel />
            </PluginSidebar>
        )
    });
})();
