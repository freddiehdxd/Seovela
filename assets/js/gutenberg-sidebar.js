/**
 * Seovela SEO Gutenberg Sidebar Panel
 *
 * Provides SEO controls in the block editor sidebar:
 * - Focus keyword input
 * - Meta title with character counter
 * - Meta description with character counter
 * - Real-time SERP preview
 * - SEO score indicator
 * - Noindex/Nofollow toggles
 * - AI generate buttons (when configured)
 *
 * Uses only wp.* global packages — no JSX, no build step required.
 *
 * @package Seovela
 * @since 2.2.0
 */

( function( wp ) {
    'use strict';

    // Bail if required packages are missing
    if ( ! wp || ! wp.plugins || ! wp.editPost || ! wp.element || ! wp.data || ! wp.components ) {
        return;
    }

    // --- Package references ---
    var el                        = wp.element.createElement;
    var Fragment                   = wp.element.Fragment;
    var useState                   = wp.element.useState;
    var useEffect                  = wp.element.useEffect;
    var useCallback                = wp.element.useCallback;
    var registerPlugin             = wp.plugins.registerPlugin;
    var PluginSidebar              = wp.editPost.PluginSidebar;
    var PluginSidebarMoreMenuItem  = wp.editPost.PluginSidebarMoreMenuItem;
    var useSelect                  = wp.data.useSelect;
    var useDispatch                = wp.data.useDispatch;
    var __                         = wp.i18n.__;

    // Components
    var TextControl     = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var ToggleControl   = wp.components.ToggleControl;
    var Panel           = wp.components.Panel;
    var PanelBody       = wp.components.PanelBody;
    var PanelRow        = wp.components.PanelRow;
    var Button          = wp.components.Button;
    var Spinner         = wp.components.Spinner;

    // Localized data from PHP
    var editorData = window.seovelaEditor || {};

    // --- SVG Icon for sidebar ---
    var seoIcon = el( 'svg', {
        width: 20,
        height: 20,
        viewBox: '0 0 24 24',
        fill: 'none',
        xmlns: 'http://www.w3.org/2000/svg'
    },
        el( 'path', {
            d: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z',
            fill: 'currentColor'
        })
    );

    // =========================================================================
    // Helper: Character count color
    // =========================================================================
    function getCountColor( length, max, warnStart ) {
        if ( length === 0 ) {
            return '#94a3b8'; // gray
        }
        if ( length <= warnStart ) {
            return '#10b981'; // green
        }
        if ( length <= max ) {
            return '#f59e0b'; // yellow/amber
        }
        return '#ef4444'; // red
    }

    // =========================================================================
    // Helper: Score color
    // =========================================================================
    function getScoreColor( score ) {
        if ( score >= 80 ) return '#10b981';
        if ( score >= 50 ) return '#f59e0b';
        if ( score > 0 )  return '#ef4444';
        return '#94a3b8';
    }

    function getScoreLabel( score ) {
        if ( score >= 80 ) return __( 'Good', 'seovela' );
        if ( score >= 50 ) return __( 'Needs Work', 'seovela' );
        if ( score > 0 )  return __( 'Poor', 'seovela' );
        return __( 'N/A', 'seovela' );
    }

    // =========================================================================
    // Component: Character Counter
    // =========================================================================
    function CharacterCounter( props ) {
        var length    = props.length || 0;
        var max       = props.max;
        var warnStart = props.warnStart || max;
        var color     = getCountColor( length, max, warnStart );

        return el( 'div', {
            style: {
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                fontSize: '12px',
                marginTop: '4px',
                padding: '0 2px'
            }
        },
            el( 'span', {
                style: {
                    color: color,
                    fontWeight: length > max ? '600' : '400',
                    transition: 'color 0.2s ease'
                }
            }, length + ' / ' + max + ' ' + __( 'characters', 'seovela' ) ),
            length > max && el( 'span', {
                style: { color: '#ef4444', fontSize: '11px' }
            }, __( 'Too long', 'seovela' ) )
        );
    }

    // =========================================================================
    // Component: SEO Score Ring
    // =========================================================================
    function SEOScoreRing( props ) {
        var score         = props.score || 0;
        var radius        = 32;
        var strokeWidth   = 5;
        var circumference = 2 * Math.PI * radius;
        var offset        = circumference - ( score / 100 ) * circumference;
        var color         = getScoreColor( score );
        var label         = getScoreLabel( score );
        var size          = ( radius + strokeWidth ) * 2;

        return el( 'div', {
            style: {
                display: 'flex',
                alignItems: 'center',
                gap: '12px',
                padding: '12px 0'
            }
        },
            el( 'div', {
                style: {
                    position: 'relative',
                    width: size + 'px',
                    height: size + 'px',
                    flexShrink: 0
                }
            },
                el( 'svg', {
                    width: size,
                    height: size,
                    viewBox: '0 0 ' + size + ' ' + size,
                    style: { transform: 'rotate(-90deg)' }
                },
                    el( 'circle', {
                        cx: size / 2,
                        cy: size / 2,
                        r: radius,
                        fill: 'none',
                        stroke: '#e5e7eb',
                        strokeWidth: strokeWidth
                    }),
                    el( 'circle', {
                        cx: size / 2,
                        cy: size / 2,
                        r: radius,
                        fill: 'none',
                        stroke: color,
                        strokeWidth: strokeWidth,
                        strokeDasharray: circumference,
                        strokeDashoffset: offset,
                        strokeLinecap: 'round',
                        style: { transition: 'stroke-dashoffset 0.6s ease, stroke 0.3s ease' }
                    })
                ),
                el( 'div', {
                    style: {
                        position: 'absolute',
                        top: '50%',
                        left: '50%',
                        transform: 'translate(-50%, -50%)',
                        textAlign: 'center',
                        lineHeight: '1'
                    }
                },
                    el( 'span', {
                        style: {
                            fontSize: '16px',
                            fontWeight: '700',
                            color: color
                        }
                    }, score )
                )
            ),
            el( 'div', null,
                el( 'div', {
                    style: {
                        fontSize: '13px',
                        fontWeight: '600',
                        color: '#1e293b',
                        marginBottom: '2px'
                    }
                }, __( 'SEO Score', 'seovela' ) ),
                el( 'div', {
                    style: {
                        fontSize: '12px',
                        color: color,
                        fontWeight: '500'
                    }
                }, label )
            )
        );
    }

    // =========================================================================
    // Component: SERP Preview
    // =========================================================================
    function SERPPreview( props ) {
        var title       = props.title || '';
        var description = props.description || '';
        var url         = props.url || '';

        // Truncate for display
        var displayTitle = title.length > 60 ? title.substring( 0, 57 ) + '...' : title;
        var displayDesc  = description.length > 160 ? description.substring( 0, 157 ) + '...' : description;

        // Parse URL for display
        var displayUrl = url;
        try {
            var parsed = new URL( url );
            displayUrl = parsed.hostname + parsed.pathname.replace( /\/$/, '' );
        } catch ( e ) {
            // keep raw url
        }

        return el( 'div', {
            style: {
                background: '#fff',
                border: '1px solid #e2e8f0',
                borderRadius: '8px',
                padding: '14px',
                fontFamily: 'Arial, sans-serif'
            }
        },
            el( 'div', {
                style: {
                    fontSize: '11px',
                    color: '#4d5156',
                    marginBottom: '4px',
                    lineHeight: '1.4',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '6px'
                }
            },
                el( 'span', {
                    style: {
                        display: 'inline-flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        width: '18px',
                        height: '18px',
                        borderRadius: '50%',
                        background: '#f1f3f4',
                        flexShrink: 0
                    }
                },
                    el( 'svg', {
                        width: 12,
                        height: 12,
                        viewBox: '0 0 24 24',
                        fill: '#70757a'
                    },
                        el( 'path', {
                            d: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z'
                        })
                    )
                ),
                el( 'span', null, displayUrl || __( 'example.com', 'seovela' ) )
            ),
            el( 'div', {
                style: {
                    fontSize: '18px',
                    lineHeight: '1.3',
                    color: '#1a0dab',
                    marginBottom: '4px',
                    fontWeight: '400',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap'
                }
            }, displayTitle || __( 'Page Title', 'seovela' ) ),
            el( 'div', {
                style: {
                    fontSize: '13px',
                    lineHeight: '1.5',
                    color: '#4d5156',
                    display: '-webkit-box',
                    WebkitLineClamp: '2',
                    WebkitBoxOrient: 'vertical',
                    overflow: 'hidden'
                }
            }, displayDesc || __( 'Add a meta description to control how this page appears in search results.', 'seovela' ) )
        );
    }

    // =========================================================================
    // Component: AI Generate Button
    // =========================================================================
    function AIGenerateButton( props ) {
        var field      = props.field;
        var onGenerate = props.onGenerate;
        var loading    = props.loading;

        if ( ! editorData.aiEnabled ) {
            return null;
        }

        return el( Button, {
            variant: 'secondary',
            isSmall: true,
            isBusy: loading,
            disabled: loading,
            onClick: function() { onGenerate( field ); },
            style: { marginTop: '4px' }
        },
            loading
                ? el( Fragment, null, el( Spinner, null ), __( 'Generating...', 'seovela' ) )
                : el( Fragment, null,
                    el( 'span', { className: 'dashicons dashicons-superhero-alt', style: { fontSize: '16px', width: '16px', height: '16px', marginRight: '4px' } } ),
                    __( 'Generate with AI', 'seovela' )
                )
        );
    }

    // =========================================================================
    // Main Sidebar Component
    // =========================================================================
    function SeovelaEditorSidebar() {

        // --- Read post meta from the store ---
        var postMeta = useSelect( function( select ) {
            return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
        }, [] );

        var postTitle = useSelect( function( select ) {
            return select( 'core/editor' ).getEditedPostAttribute( 'title' ) || '';
        }, [] );

        var postLink = useSelect( function( select ) {
            return select( 'core/editor' ).getPermalink() || editorData.siteUrl || '';
        }, [] );

        var editPost = useDispatch( 'core/editor' ).editPost;

        // --- Local state for AI loading ---
        var _aiTitleLoading = useState( false );
        var aiTitleLoading  = _aiTitleLoading[0];
        var setAiTitleLoading = _aiTitleLoading[1];

        var _aiDescLoading = useState( false );
        var aiDescLoading  = _aiDescLoading[0];
        var setAiDescLoading = _aiDescLoading[1];

        // --- Meta values ---
        var focusKeyword   = postMeta._seovela_focus_keyword || '';
        var metaTitle      = postMeta._seovela_meta_title || '';
        var metaDesc       = postMeta._seovela_meta_description || '';
        var noindex        = !! postMeta._seovela_noindex;
        var nofollow       = !! postMeta._seovela_nofollow;
        var seoScore       = parseInt( postMeta._seovela_seo_score, 10 ) || 0;

        // Use post title as fallback for SERP preview
        var displayTitle = metaTitle || postTitle;

        // --- Update meta helper ---
        function updateMeta( key, value ) {
            var meta = {};
            meta[ key ] = value;
            editPost( { meta: meta } );
        }

        // --- AI Generate handler ---
        function handleAIGenerate( field ) {
            if ( ! editorData.aiEnabled || ! editorData.ajaxUrl || ! editorData.aiNonce ) {
                return;
            }

            var setLoading = field === 'title' ? setAiTitleLoading : setAiDescLoading;
            setLoading( true );

            var formData = new FormData();
            formData.append( 'action', 'seovela_ai_generate' );
            formData.append( 'nonce', editorData.aiNonce );
            formData.append( 'field', field );
            formData.append( 'post_title', postTitle );
            formData.append( 'focus_keyword', focusKeyword );
            formData.append( 'current_title', metaTitle );
            formData.append( 'current_description', metaDesc );

            fetch( editorData.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            } )
            .then( function( response ) { return response.json(); } )
            .then( function( data ) {
                setLoading( false );
                if ( data.success && data.data ) {
                    if ( field === 'title' && data.data.title ) {
                        updateMeta( '_seovela_meta_title', data.data.title );
                    } else if ( field === 'description' && data.data.description ) {
                        updateMeta( '_seovela_meta_description', data.data.description );
                    }
                }
            } )
            .catch( function() {
                setLoading( false );
            } );
        }

        // --- Render ---
        return el( Fragment, null,

            // Menu item in the "More" menu
            el( PluginSidebarMoreMenuItem, {
                target: 'seovela-seo-sidebar',
                icon: seoIcon
            }, __( 'Seovela SEO', 'seovela' ) ),

            // The sidebar itself
            el( PluginSidebar, {
                name: 'seovela-seo-sidebar',
                title: __( 'Seovela SEO', 'seovela' ),
                icon: seoIcon
            },
                el( 'div', { className: 'seovela-gutenberg-sidebar', style: { padding: '0' } },

                    // ==========  SEO Score  ==========
                    el( Panel, null,
                        el( PanelBody, {
                            title: __( 'SEO Score', 'seovela' ),
                            initialOpen: true
                        },
                            el( PanelRow, null,
                                el( SEOScoreRing, { score: seoScore } )
                            )
                        )
                    ),

                    // ==========  Focus Keyword  ==========
                    el( Panel, null,
                        el( PanelBody, {
                            title: __( 'Focus Keyword', 'seovela' ),
                            initialOpen: true
                        },
                            el( PanelRow, null,
                                el( 'div', { style: { width: '100%' } },
                                    el( TextControl, {
                                        label: __( 'Keyword or phrase', 'seovela' ),
                                        help: __( 'The main keyword you want this content to rank for.', 'seovela' ),
                                        value: focusKeyword,
                                        onChange: function( val ) { updateMeta( '_seovela_focus_keyword', val ); },
                                        placeholder: __( 'e.g., WordPress SEO plugin', 'seovela' )
                                    })
                                )
                            )
                        )
                    ),

                    // ==========  Meta Title  ==========
                    el( Panel, null,
                        el( PanelBody, {
                            title: __( 'Meta Title', 'seovela' ),
                            initialOpen: true
                        },
                            el( PanelRow, null,
                                el( 'div', { style: { width: '100%' } },
                                    el( TextControl, {
                                        value: metaTitle,
                                        onChange: function( val ) { updateMeta( '_seovela_meta_title', val ); },
                                        placeholder: postTitle || __( 'Enter meta title...', 'seovela' ),
                                        maxLength: 70
                                    }),
                                    el( CharacterCounter, {
                                        length: metaTitle.length,
                                        max: 60,
                                        warnStart: 50
                                    }),
                                    el( AIGenerateButton, {
                                        field: 'title',
                                        onGenerate: handleAIGenerate,
                                        loading: aiTitleLoading
                                    })
                                )
                            )
                        )
                    ),

                    // ==========  Meta Description  ==========
                    el( Panel, null,
                        el( PanelBody, {
                            title: __( 'Meta Description', 'seovela' ),
                            initialOpen: true
                        },
                            el( PanelRow, null,
                                el( 'div', { style: { width: '100%' } },
                                    el( TextareaControl, {
                                        value: metaDesc,
                                        onChange: function( val ) { updateMeta( '_seovela_meta_description', val ); },
                                        placeholder: __( 'Enter meta description...', 'seovela' ),
                                        rows: 3
                                    }),
                                    el( CharacterCounter, {
                                        length: metaDesc.length,
                                        max: 160,
                                        warnStart: 120
                                    }),
                                    el( AIGenerateButton, {
                                        field: 'description',
                                        onGenerate: handleAIGenerate,
                                        loading: aiDescLoading
                                    })
                                )
                            )
                        )
                    ),

                    // ==========  SERP Preview  ==========
                    el( Panel, null,
                        el( PanelBody, {
                            title: __( 'SERP Preview', 'seovela' ),
                            initialOpen: true
                        },
                            el( PanelRow, null,
                                el( 'div', { style: { width: '100%' } },
                                    el( SERPPreview, {
                                        title: displayTitle,
                                        description: metaDesc,
                                        url: postLink
                                    })
                                )
                            )
                        )
                    ),

                    // ==========  Indexing Settings  ==========
                    el( Panel, null,
                        el( PanelBody, {
                            title: __( 'Indexing Settings', 'seovela' ),
                            initialOpen: false
                        },
                            el( PanelRow, null,
                                el( ToggleControl, {
                                    label: __( 'Noindex', 'seovela' ),
                                    help: noindex
                                        ? __( 'This page is hidden from search engines.', 'seovela' )
                                        : __( 'This page can be indexed by search engines.', 'seovela' ),
                                    checked: noindex,
                                    onChange: function( val ) { updateMeta( '_seovela_noindex', val ); }
                                })
                            ),
                            el( PanelRow, null,
                                el( ToggleControl, {
                                    label: __( 'Nofollow', 'seovela' ),
                                    help: nofollow
                                        ? __( 'Search engines will not follow links on this page.', 'seovela' )
                                        : __( 'Search engines will follow links on this page.', 'seovela' ),
                                    checked: nofollow,
                                    onChange: function( val ) { updateMeta( '_seovela_nofollow', val ); }
                                })
                            )
                        )
                    )
                )
            )
        );
    }

    // =========================================================================
    // Register the plugin
    // =========================================================================
    registerPlugin( 'seovela-seo', {
        render: SeovelaEditorSidebar,
        icon: seoIcon
    } );

} )( window.wp );
