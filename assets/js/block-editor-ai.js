/**
 * Seovela AI Block Editor Integration with Streaming
 *
 * Adds AI sidebar panel to Gutenberg with real-time streaming
 * Shows Pro teaser for free users
 *
 * @package Seovela
 */

(function(wp) {
    'use strict';

    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var PluginSidebar = wp.editPost.PluginSidebar;
    var PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;
    var registerPlugin = wp.plugins.registerPlugin;
    var useState = wp.element.useState;
    var useRef = wp.element.useRef;
    var useEffect = wp.element.useEffect;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var Button = wp.components.Button;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var Spinner = wp.components.Spinner;
    var __ = wp.i18n.__;
    var createBlock = wp.blocks.createBlock;

    // AI Icon
    var AIIcon = el('svg', {
        width: 20,
        height: 20,
        viewBox: '0 0 24 24',
        fill: 'currentColor'
    }, el('path', {
        d: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'
    }));

    // Pro Teaser Component
    function SeovelaProTeaser() {
        return el('div', { className: 'seovela-ai-sidebar seovela-ai-pro-teaser' },
            el('div', { className: 'seovela-ai-teaser-content' },
                el('div', { className: 'seovela-ai-teaser-icon' },
                    el('span', { className: 'dashicons dashicons-superhero-alt' })
                ),
                el('span', { className: 'seovela-ai-pro-badge' }, seovelaAI.i18n.proFeature),
                el('h3', null, seovelaAI.i18n.proTitle),
                el('p', null, seovelaAI.i18n.proDescription),
                el('ul', { className: 'seovela-ai-teaser-features' },
                    el('li', null,
                        el('span', { className: 'dashicons dashicons-yes-alt' }),
                        seovelaAI.i18n.proFeature1
                    ),
                    el('li', null,
                        el('span', { className: 'dashicons dashicons-yes-alt' }),
                        seovelaAI.i18n.proFeature2
                    ),
                    el('li', null,
                        el('span', { className: 'dashicons dashicons-yes-alt' }),
                        seovelaAI.i18n.proFeature3
                    ),
                    el('li', null,
                        el('span', { className: 'dashicons dashicons-yes-alt' }),
                        seovelaAI.i18n.proFeature4
                    )
                ),
                el('a', {
                    href: seovelaAI.upgradeUrl,
                    className: 'seovela-ai-upgrade-btn',
                    target: '_blank',
                    rel: 'noopener noreferrer'
                },
                    el('span', { className: 'dashicons dashicons-unlock' }),
                    seovelaAI.i18n.upgradeToPro
                )
            )
        );
    }

    // AI Not Configured Component
    function SeovelaConfigureAI() {
        return el('div', { className: 'seovela-ai-sidebar seovela-ai-configure' },
            el('div', { className: 'seovela-ai-configure-content' },
                el('div', { className: 'seovela-ai-configure-icon' },
                    el('span', { className: 'dashicons dashicons-admin-settings' })
                ),
                el('h3', null, seovelaAI.i18n.aiNotConfigured),
                el('p', null, seovelaAI.i18n.aiNotConfiguredDesc),
                el('a', {
                    href: seovelaAI.settingsUrl,
                    className: 'seovela-ai-configure-btn'
                },
                    el('span', { className: 'dashicons dashicons-admin-settings' }),
                    seovelaAI.i18n.configureAI
                )
            )
        );
    }

    // Main Plugin Component
    function SeovelaAIPanel() {
        // Check if Pro is active
        if (!seovelaAI.isPro) {
            return el(Fragment, null,
                el(PluginSidebarMoreMenuItem, {
                    target: 'seovela-ai-sidebar'
                },
                    el('span', { className: 'dashicons dashicons-superhero-alt', style: { marginRight: '6px' } }),
                    seovelaAI.i18n.title,
                    el('span', { className: 'seovela-menu-pro-badge' }, 'PRO')
                ),
                el(PluginSidebar, {
                    name: 'seovela-ai-sidebar',
                    title: seovelaAI.i18n.title,
                    icon: AIIcon
                },
                    el(SeovelaProTeaser, null)
                )
            );
        }

        // Check if AI is configured
        if (!seovelaAI.isConfigured) {
            return el(Fragment, null,
                el(PluginSidebarMoreMenuItem, {
                    target: 'seovela-ai-sidebar'
                },
                    el('span', { className: 'dashicons dashicons-superhero-alt', style: { marginRight: '6px' } }),
                    seovelaAI.i18n.title
                ),
                el(PluginSidebar, {
                    name: 'seovela-ai-sidebar',
                    title: seovelaAI.i18n.title,
                    icon: AIIcon
                },
                    el(SeovelaConfigureAI, null)
                )
            );
        }

        // Full AI functionality for Pro users with configured API
        var _useState = useState('improve'),
            activeTab = _useState[0],
            setActiveTab = _useState[1];

        var _useState2 = useState(false),
            isStreaming = _useState2[0],
            setIsStreaming = _useState2[1];

        var _useState3 = useState(''),
            topic = _useState3[0],
            setTopic = _useState3[1];

        var _useState4 = useState('article'),
            contentType = _useState4[0],
            setContentType = _useState4[1];

        var _useState5 = useState('professional'),
            tone = _useState5[0],
            setTone = _useState5[1];

        var _useState6 = useState(''),
            streamedContent = _useState6[0],
            setStreamedContent = _useState6[1];

        var _useState7 = useState(false),
            showOutput = _useState7[0],
            setShowOutput = _useState7[1];

        var streamedContentRef = useRef('');

        var blocks = useSelect(function(select) {
            return select('core/block-editor').getBlocks();
        });

        var selectedBlock = useSelect(function(select) {
            return select('core/block-editor').getSelectedBlock();
        });

        var _useDispatch = useDispatch('core/block-editor'),
            insertBlocks = _useDispatch.insertBlocks,
            resetBlocks = _useDispatch.resetBlocks;

        // Get content from blocks
        function getEditorContent() {
            var content = '';
            
            // First check if there's a selected block with content
            if (selectedBlock && selectedBlock.attributes) {
                if (selectedBlock.attributes.content) {
                    return {
                        content: selectedBlock.attributes.content,
                        isSelection: true
                    };
                }
            }

            // Otherwise get all content
            blocks.forEach(function(block) {
                if (block.name === 'core/paragraph' && block.attributes.content) {
                    content += block.attributes.content + '\n';
                } else if (block.name === 'core/heading' && block.attributes.content) {
                    content += block.attributes.content + '\n';
                } else if (block.name === 'core/list' && block.attributes.values) {
                    content += block.attributes.values + '\n';
                }
            });

            return {
                content: content.trim(),
                isSelection: false
            };
        }

        // Convert HTML to blocks
        function htmlToBlocks(html) {
            var blocksToInsert = [];
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            tempDiv.childNodes.forEach(function(node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    var tagName = node.tagName.toLowerCase();

                    if (tagName === 'h1' || tagName === 'h2' || tagName === 'h3' || tagName === 'h4') {
                        blocksToInsert.push(createBlock('core/heading', {
                            level: parseInt(tagName.charAt(1)),
                            content: node.innerHTML
                        }));
                    } else if (tagName === 'p') {
                        blocksToInsert.push(createBlock('core/paragraph', {
                            content: node.innerHTML
                        }));
                    } else if (tagName === 'ul' || tagName === 'ol') {
                        blocksToInsert.push(createBlock('core/list', {
                            ordered: tagName === 'ol',
                            values: node.innerHTML
                        }));
                    } else {
                        blocksToInsert.push(createBlock('core/paragraph', {
                            content: node.innerHTML || node.textContent
                        }));
                    }
                } else if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                    blocksToInsert.push(createBlock('core/paragraph', {
                        content: node.textContent.trim()
                    }));
                }
            });

            return blocksToInsert.length > 0 ? blocksToInsert : [createBlock('core/paragraph', { content: html })];
        }

        // Stream from API
        function streamFromAPI(params) {
            var url = seovelaAI.restUrl + 'seovela/v1/ai-stream';
            streamedContentRef.current = '';
            setStreamedContent('');
            setShowOutput(true);
            setIsStreaming(true);

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': seovelaAI.restNonce
                },
                body: JSON.stringify(params)
            }).then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                if (!response.body) {
                    throw new Error('Streaming not supported');
                }

                var reader = response.body.getReader();
                var decoder = new TextDecoder();

                function readStream() {
                    reader.read().then(function(result) {
                        if (result.done) {
                            setIsStreaming(false);
                            return;
                        }

                        var chunk = decoder.decode(result.value, { stream: true });
                        var lines = chunk.split('\n');

                        for (var i = 0; i < lines.length; i++) {
                            var line = lines[i].trim();
                            
                            if (!line.startsWith('data:')) {
                                continue;
                            }

                            var data = line.substring(5).trim();
                            
                            if (data === '[DONE]') {
                                setIsStreaming(false);
                                return;
                            }

                            try {
                                var parsed = JSON.parse(data);
                                
                                if (parsed.error) {
                                    alert(parsed.error);
                                    setIsStreaming(false);
                                    setShowOutput(false);
                                    return;
                                }

                                if (parsed.content) {
                                    streamedContentRef.current += parsed.content;
                                    setStreamedContent(streamedContentRef.current);
                                }
                            } catch (e) {
                                // Skip invalid JSON
                            }
                        }

                        // Continue reading
                        readStream();
                    }).catch(function(error) {
                        console.error('Stream read error:', error);
                        setIsStreaming(false);
                    });
                }

                readStream();

            }).catch(function(error) {
                console.error('Fetch error:', error);
                setIsStreaming(false);
                setShowOutput(false);
                alert(seovelaAI.i18n.error);
            });
        }

        // Improve content action
        function handleImprove(actionType) {
            var editorData = getEditorContent();

            if (!editorData.content || editorData.content.length < 20) {
                alert(seovelaAI.i18n.noContent || 'Please add more content first.');
                return;
            }

            streamFromAPI({
                action_type: actionType,
                content: editorData.content,
                focus_keyword: ''
            });
        }

        // Write content action
        function handleWrite() {
            if (!topic) {
                alert(seovelaAI.i18n.enterTopic || 'Please enter a topic.');
                return;
            }

            streamFromAPI({
                action_type: 'write',
                topic: topic,
                content_type: contentType,
                tone: tone,
                focus_keyword: ''
            });
        }

        // Insert generated content
        function handleInsert(replaceAll) {
            var newBlocks = htmlToBlocks(streamedContent);

            if (replaceAll) {
                resetBlocks(newBlocks);
            } else {
                insertBlocks(newBlocks);
            }

            setShowOutput(false);
            setStreamedContent('');
            streamedContentRef.current = '';
        }

        // Discard content
        function handleDiscard() {
            setShowOutput(false);
            setStreamedContent('');
            streamedContentRef.current = '';
        }

        // Render improve buttons
        function renderImproveButtons() {
            var actions = [
                { key: 'improve', label: seovelaAI.i18n.improveReadability, icon: 'edit' },
                { key: 'expand', label: seovelaAI.i18n.expandContent, icon: 'plus-alt' },
                { key: 'seo_optimize', label: seovelaAI.i18n.seoOptimize, icon: 'chart-line' },
                { key: 'simplify', label: seovelaAI.i18n.simplify, icon: 'editor-textcolor' },
                { key: 'shorten', label: seovelaAI.i18n.shorten, icon: 'editor-contract' }
            ];

            return el('div', { className: 'seovela-ai-actions' },
                actions.map(function(action) {
                    return el(Button, {
                        key: action.key,
                        className: 'seovela-ai-action-btn',
                        onClick: function() { handleImprove(action.key); },
                        disabled: isStreaming
                    },
                        el('span', { className: 'dashicons dashicons-' + action.icon }),
                        action.label
                    );
                })
            );
        }

        // Render write form
        function renderWriteForm() {
            return el('div', { className: 'seovela-ai-write-form' },
                el(TextControl, {
                    label: seovelaAI.i18n.topic,
                    value: topic,
                    onChange: setTopic,
                    placeholder: seovelaAI.i18n.topicPlaceholder,
                    disabled: isStreaming
                }),
                el('div', { className: 'seovela-ai-row' },
                    el(SelectControl, {
                        label: seovelaAI.i18n.contentType,
                        value: contentType,
                        onChange: setContentType,
                        disabled: isStreaming,
                        options: [
                            { label: seovelaAI.i18n.article, value: 'article' },
                            { label: seovelaAI.i18n.listicle, value: 'listicle' },
                            { label: seovelaAI.i18n.howTo, value: 'how-to' },
                            { label: seovelaAI.i18n.comparison, value: 'comparison' },
                            { label: seovelaAI.i18n.review, value: 'review' }
                        ]
                    }),
                    el(SelectControl, {
                        label: seovelaAI.i18n.tone,
                        value: tone,
                        onChange: setTone,
                        disabled: isStreaming,
                        options: [
                            { label: seovelaAI.i18n.professional, value: 'professional' },
                            { label: seovelaAI.i18n.casual, value: 'casual' },
                            { label: seovelaAI.i18n.friendly, value: 'friendly' },
                            { label: seovelaAI.i18n.formal, value: 'formal' }
                        ]
                    })
                ),
                el(Button, {
                    isPrimary: true,
                    className: 'seovela-ai-generate-btn',
                    onClick: handleWrite,
                    disabled: isStreaming
                },
                    el('span', { className: 'dashicons dashicons-superhero-alt' }),
                    isStreaming ? seovelaAI.i18n.generating : seovelaAI.i18n.generate
                )
            );
        }

        // Render output preview with streaming cursor
        function renderOutput() {
            if (!showOutput) return null;

            var displayContent = streamedContent + (isStreaming ? '<span class="seovela-streaming-cursor"></span>' : '');

            return el('div', { className: 'seovela-ai-output' },
                el('div', { className: 'seovela-ai-output-header' },
                    el('strong', null, isStreaming ? 'Generating...' : 'Generated Content')
                ),
                el('div', {
                    className: 'seovela-ai-output-content',
                    dangerouslySetInnerHTML: { __html: displayContent }
                }),
                !isStreaming && el('div', { className: 'seovela-ai-output-actions' },
                    el(Button, {
                        isPrimary: true,
                        onClick: function() { handleInsert(false); }
                    },
                        el('span', { className: 'dashicons dashicons-yes' }),
                        seovelaAI.i18n.insert
                    ),
                    el(Button, {
                        isSecondary: true,
                        onClick: function() { handleInsert(true); }
                    },
                        el('span', { className: 'dashicons dashicons-update' }),
                        seovelaAI.i18n.replace
                    ),
                    el(Button, {
                        isSecondary: true,
                        onClick: handleDiscard
                    },
                        el('span', { className: 'dashicons dashicons-no' }),
                        seovelaAI.i18n.discard
                    )
                )
            );
        }

        // Tab content
        function renderTabContent() {
            if (showOutput) {
                return renderOutput();
            }

            if (activeTab === 'improve') {
                return el('div', { className: 'seovela-ai-tab-content' },
                    el('p', { className: 'description' }, 'Select a block or improve all content:'),
                    renderImproveButtons()
                );
            }

            return el('div', { className: 'seovela-ai-tab-content' },
                renderWriteForm()
            );
        }

        return el(Fragment, null,
            el(PluginSidebarMoreMenuItem, {
                target: 'seovela-ai-sidebar'
            },
                el('span', { className: 'dashicons dashicons-superhero-alt', style: { marginRight: '6px' } }),
                seovelaAI.i18n.title
            ),
            el(PluginSidebar, {
                name: 'seovela-ai-sidebar',
                title: seovelaAI.i18n.title,
                icon: AIIcon
            },
                el('div', { className: 'seovela-ai-sidebar' },
                    el('div', { className: 'seovela-ai-tabs' },
                        el('button', {
                            className: 'seovela-ai-tab' + (activeTab === 'improve' ? ' active' : ''),
                            onClick: function() { setActiveTab('improve'); },
                            disabled: isStreaming
                        },
                            el('span', { className: 'dashicons dashicons-edit' }),
                            seovelaAI.i18n.improve
                        ),
                        el('button', {
                            className: 'seovela-ai-tab' + (activeTab === 'write' ? ' active' : ''),
                            onClick: function() { setActiveTab('write'); },
                            disabled: isStreaming
                        },
                            el('span', { className: 'dashicons dashicons-welcome-write-blog' }),
                            seovelaAI.i18n.write
                        )
                    ),
                    renderTabContent()
                )
            )
        );
    }

    // Register the plugin
    registerPlugin('seovela-ai', {
        render: SeovelaAIPanel,
        icon: AIIcon
    });

})(window.wp);
