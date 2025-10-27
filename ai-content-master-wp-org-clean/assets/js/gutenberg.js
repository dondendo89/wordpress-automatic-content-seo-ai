/**
 * Gutenberg integration JavaScript
 *
 * @package AI_Content_Master
 * @since 1.0.0
 */

(function() {
    'use strict';

    const { registerPlugin } = wp.plugins;
    const { PluginSidebar } = wp.editPost;
    const { PanelBody, Button, Spinner } = wp.components;
    const { useState, useEffect } = wp.element;
    const { useSelect, useDispatch } = wp.data;
    const { apiFetch } = wp.apiFetch;

    function ACMGutenbergPanel() {
        const [status, setStatus] = useState(null);
        const [isLoading, setIsLoading] = useState(false);
        const [creditsInfo, setCreditsInfo] = useState(acmGutenberg.creditsInfo);

        const { editPost } = useDispatch('core/editor');
        const postId = useSelect((select) => select('core/editor').getCurrentPostId());

        // Update credits info
        useEffect(() => {
            updateCreditsInfo();
        }, []);

        const updateCreditsInfo = async () => {
            try {
                const response = await apiFetch({
                    path: '/ai-content-master/v1/credits',
                    method: 'GET'
                });

                if (response.success) {
                    setCreditsInfo(response.credits_info);
                }
            } catch (error) {
                console.error('Failed to fetch credits info:', error);
            }
        };

        const generateContent = async (type) => {
            setIsLoading(true);
            setStatus({ type: 'loading', message: acmGutenberg.strings.generating || 'Generating...' });

            try {
                const response = await apiFetch({
                    path: '/ai-content-master/v1/generate',
                    method: 'POST',
                    data: {
                        postId: postId,
                        type: type
                    }
                });

                if (response.success) {
                    setStatus({ type: 'success', message: acmGutenberg.strings.success || 'Generated successfully!' });
                    
                    // Update editor content
                    if (type === 'content' && response.content) {
                        editPost({ content: response.content });
                    } else if (type === 'excerpt' && response.excerpt) {
                        editPost({ excerpt: response.excerpt });
                    }

                    // Update credits info
                    if (response.credits_info) {
                        setCreditsInfo(response.credits_info);
                    }
                } else {
                    setStatus({ type: 'error', message: response.message || acmGutenberg.strings.error || 'Generation failed' });
                }
            } catch (error) {
                if (error.code === 'no_credits') {
                    setStatus({ 
                        type: 'error', 
                        message: acmGutenberg.strings.no_credits || 'No credits remaining. Please purchase more credits.' 
                    });
                } else {
                    setStatus({ type: 'error', message: 'Network error occurred' });
                }
            } finally {
                setIsLoading(false);
            }
        };

        const canGenerate = () => {
            return creditsInfo.free_generations_remaining > 0 || creditsInfo.credits_remaining > 0;
        };

        return (
            <PanelBody title="AI Content Generator" initialOpen={true}>
                <div className="acm-gutenberg-panel">
                    {canGenerate() ? (
                        <>
                            <div className="acm-gutenberg-credits-info">
                                {creditsInfo.free_generations_remaining > 0 ? (
                                    <div>{creditsInfo.free_generations_remaining} free generations remaining</div>
                                ) : (
                                    <div>{creditsInfo.credits_remaining} credit generations remaining</div>
                                )}
                            </div>

                            <div className="acm-gutenberg-buttons">
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
                        </>
                    ) : (
                        <div className="acm-gutenberg-no-credits">
                            <div>No free generations or credits remaining.</div>
                            <Button
                                variant="primary"
                                onClick={() => window.open(acmGutenberg.ajax_url.replace('admin-ajax.php', 'admin.php?page=acm-credits'), '_blank')}
                            >
                                Buy Credits
                            </Button>
                        </div>
                    )}

                    {status && (
                        <div className={`acm-gutenberg-status ${status.type}`}>
                            {isLoading && <Spinner />}
                            {status.message}
                        </div>
                    )}
                </div>
            </PanelBody>
        );
    }

    registerPlugin('ai-content-master', {
        render: () => (
            <PluginSidebar
                name="ai-content-master"
                title="AI Content Generator"
                icon="admin-tools"
            >
                <ACMGutenbergPanel />
            </PluginSidebar>
        )
    });
})();
