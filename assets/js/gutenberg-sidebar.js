/**
 * Seovela SEO Gutenberg Sidebar Panel
 *
 * Full-featured SEO controls in the block editor sidebar:
 * - SEO score with Refresh Analysis / View Analysis
 * - Focus keyword with AI Suggest
 * - Meta title with character counter + AI Generate
 * - Meta description with character counter + AI Generate
 * - Real-time SERP preview
 * - Noindex/Nofollow toggles
 * - Schema Markup controls
 *
 * Uses only wp.* global packages — no JSX, no build step required.
 *
 * @package Seovela
 * @since 2.3.0
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
    var SelectControl   = wp.components.SelectControl;
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
        if ( length === 0 ) return '#94a3b8';
        if ( length <= warnStart ) return '#10b981';
        if ( length <= max ) return '#f59e0b';
        return '#ef4444';
    }

    // =========================================================================
    // Helper: Score color & label
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
        var label     = '';
        if ( length === 0 ) label = __( 'Empty', 'seovela' );
        else if ( length < warnStart ) label = __( 'Too short', 'seovela' );
        else if ( length > max ) label = __( 'Too long', 'seovela' );
        else label = __( 'Good length', 'seovela' );

        return el( 'div', {
            style: {
                display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                fontSize: '12px', marginTop: '4px', padding: '0 2px'
            }
        },
            el( 'span', { style: { color: color, fontWeight: length > max ? '600' : '400' } },
                length + ' / ' + max + ' ' + __( 'characters', 'seovela' )
            ),
            el( 'span', { style: { color: color, fontSize: '11px' } }, label )
        );
    }

    // =========================================================================
    // Component: SEO Score Ring
    // =========================================================================
    function SEOScoreRing( props ) {
        var score = props.score || 0;
        var radius = 32;
        var strokeWidth = 5;
        var circumference = 2 * Math.PI * radius;
        var offset = circumference - ( score / 100 ) * circumference;
        var color = getScoreColor( score );
        var label = getScoreLabel( score );
        var size = ( radius + strokeWidth ) * 2;

        return el( 'div', { style: { display: 'flex', alignItems: 'center', gap: '12px', padding: '8px 0' } },
            el( 'div', { style: { position: 'relative', width: size + 'px', height: size + 'px', flexShrink: 0 } },
                el( 'svg', { width: size, height: size, viewBox: '0 0 ' + size + ' ' + size, style: { transform: 'rotate(-90deg)' } },
                    el( 'circle', { cx: size / 2, cy: size / 2, r: radius, fill: 'none', stroke: '#e5e7eb', strokeWidth: strokeWidth }),
                    el( 'circle', { cx: size / 2, cy: size / 2, r: radius, fill: 'none', stroke: color, strokeWidth: strokeWidth,
                        strokeDasharray: circumference, strokeDashoffset: offset, strokeLinecap: 'round',
                        style: { transition: 'stroke-dashoffset 0.6s ease, stroke 0.3s ease' } })
                ),
                el( 'div', {
                    style: { position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', textAlign: 'center', lineHeight: '1' }
                },
                    el( 'span', { style: { fontSize: '16px', fontWeight: '700', color: color } }, score )
                )
            ),
            el( 'div', null,
                el( 'div', { style: { fontSize: '13px', fontWeight: '600', color: color, marginBottom: '2px' } }, label ),
                el( 'div', { style: { fontSize: '11px', color: '#64748b' } }, __( 'SEO Score', 'seovela' ) )
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
        var displayTitle = title.length > 60 ? title.substring( 0, 57 ) + '...' : title;
        var displayDesc  = description.length > 160 ? description.substring( 0, 157 ) + '...' : description;
        var displayUrl = url;
        try { var p = new URL( url ); displayUrl = p.hostname + p.pathname.replace( /\/$/, '' ); } catch ( e ) {}

        return el( 'div', {
            style: { background: '#fff', border: '1px solid #e2e8f0', borderRadius: '8px', padding: '14px', fontFamily: 'Arial, sans-serif' }
        },
            el( 'div', { style: { fontSize: '11px', color: '#4d5156', marginBottom: '4px', lineHeight: '1.4' } },
                displayUrl || 'example.com'
            ),
            el( 'div', {
                style: { fontSize: '18px', lineHeight: '1.3', color: '#1a0dab', marginBottom: '4px', fontWeight: '400', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }
            }, displayTitle || __( 'Page Title', 'seovela' ) ),
            el( 'div', {
                style: { fontSize: '13px', lineHeight: '1.5', color: '#4d5156', display: '-webkit-box', WebkitLineClamp: '2', WebkitBoxOrient: 'vertical', overflow: 'hidden' }
            }, displayDesc || __( 'Add a meta description to control how this page appears in search results.', 'seovela' ) )
        );
    }

    // =========================================================================
    // Component: AI Generate Button
    // =========================================================================
    function AIGenerateButton( props ) {
        if ( ! editorData.aiEnabled ) return null;
        return el( Button, {
            variant: 'secondary', isSmall: true, isBusy: props.loading, disabled: props.loading,
            onClick: function() { props.onGenerate( props.field ); },
            style: { marginTop: '4px' }
        },
            props.loading
                ? el( Fragment, null, el( Spinner, null ), ' ' + __( 'Generating...', 'seovela' ) )
                : el( Fragment, null,
                    el( 'span', { className: 'dashicons dashicons-superhero-alt', style: { fontSize: '16px', width: '16px', height: '16px', marginRight: '4px' } } ),
                    __( 'Generate with AI', 'seovela' )
                )
        );
    }

    // =========================================================================
    // Component: Analysis Results
    // =========================================================================
    function AnalysisResults( props ) {
        var results = props.results;
        if ( ! results ) return null;

        function renderSection( items, icon, title, color ) {
            if ( ! items || items.length === 0 ) return null;
            return el( 'div', { style: { marginBottom: '12px' } },
                el( 'div', { style: { fontSize: '12px', fontWeight: '600', color: color, marginBottom: '4px' } },
                    icon + ' ' + title + ' (' + items.length + ')'
                ),
                el( 'ul', { style: { margin: '0', padding: '0 0 0 16px', fontSize: '12px', color: '#475569', lineHeight: '1.6' } },
                    items.map( function( item, i ) {
                        return el( 'li', { key: i }, item );
                    } )
                )
            );
        }

        return el( 'div', { style: { marginTop: '8px' } },
            renderSection( results.errors, '\u274C', __( 'Errors', 'seovela' ), '#ef4444' ),
            renderSection( results.warnings, '\u26A0\uFE0F', __( 'Warnings', 'seovela' ), '#f59e0b' ),
            renderSection( results.good, '\u2713', __( 'Good', 'seovela' ), '#10b981' )
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

        var postContent = useSelect( function( select ) {
            return select( 'core/editor' ).getEditedPostContent() || '';
        }, [] );

        var postLink = useSelect( function( select ) {
            return select( 'core/editor' ).getPermalink() || editorData.siteUrl || '';
        }, [] );

        var postId = useSelect( function( select ) {
            return select( 'core/editor' ).getCurrentPostId();
        }, [] );

        var editPost = useDispatch( 'core/editor' ).editPost;

        // --- State ---
        var _aiTitleLoading = useState( false );
        var aiTitleLoading = _aiTitleLoading[0], setAiTitleLoading = _aiTitleLoading[1];

        var _aiDescLoading = useState( false );
        var aiDescLoading = _aiDescLoading[0], setAiDescLoading = _aiDescLoading[1];

        var _analysisLoading = useState( false );
        var analysisLoading = _analysisLoading[0], setAnalysisLoading = _analysisLoading[1];

        var _analysisResults = useState( null );
        var analysisResults = _analysisResults[0], setAnalysisResults = _analysisResults[1];

        var _showAnalysis = useState( false );
        var showAnalysis = _showAnalysis[0], setShowAnalysis = _showAnalysis[1];

        var _keywordLoading = useState( false );
        var keywordLoading = _keywordLoading[0], setKeywordLoading = _keywordLoading[1];

        var _suggestions = useState( [] );
        var suggestions = _suggestions[0], setSuggestions = _suggestions[1];

        // --- Meta values ---
        var focusKeyword = postMeta._seovela_focus_keyword || '';
        var metaTitle    = postMeta._seovela_meta_title || '';
        var metaDesc     = postMeta._seovela_meta_description || '';
        var noindex      = !! postMeta._seovela_noindex;
        var nofollow     = !! postMeta._seovela_nofollow;
        var seoScore     = parseInt( postMeta._seovela_seo_score, 10 ) || 0;
        var schemaType   = postMeta._seovela_schema_type || 'auto';
        var disableSchema = postMeta._seovela_disable_schema || '';

        var displayTitle = metaTitle || postTitle;

        // --- Update meta helper ---
        function updateMeta( key, value ) {
            var meta = {};
            meta[ key ] = value;
            editPost( { meta: meta } );
        }

        // --- Refresh Analysis ---
        function handleRefreshAnalysis() {
            setAnalysisLoading( true );
            setShowAnalysis( true );

            var formData = new FormData();
            formData.append( 'action', 'seovela_analyze_content' );
            formData.append( 'nonce', editorData.analysisNonce );
            formData.append( 'post_id', postId || 0 );
            formData.append( 'focus_keyword', focusKeyword );
            formData.append( 'title', metaTitle || postTitle );
            formData.append( 'description', metaDesc );
            formData.append( 'content', postContent );
            formData.append( 'url', postLink );

            fetch( editorData.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
                .then( function( r ) { return r.json(); } )
                .then( function( data ) {
                    setAnalysisLoading( false );
                    if ( data.success && data.data ) {
                        setAnalysisResults( data.data );
                        if ( typeof data.data.score === 'number' ) {
                            updateMeta( '_seovela_seo_score', data.data.score );
                        }
                    }
                } )
                .catch( function() { setAnalysisLoading( false ); } );
        }

        // --- AI Keyword Suggest ---
        function handleSuggestKeywords() {
            if ( ! editorData.aiEnabled || ! editorData.aiNonce ) return;
            setKeywordLoading( true );
            setSuggestions( [] );

            var formData = new FormData();
            formData.append( 'action', 'seovela_suggest_keywords' );
            formData.append( 'nonce', editorData.aiNonce );
            formData.append( 'title', postTitle );
            formData.append( 'content', postContent.substring( 0, 3000 ) );

            fetch( editorData.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
                .then( function( r ) { return r.json(); } )
                .then( function( data ) {
                    setKeywordLoading( false );
                    if ( data.success && data.data && data.data.keywords ) {
                        setSuggestions( data.data.keywords );
                    }
                } )
                .catch( function() { setKeywordLoading( false ); } );
        }

        // --- AI Generate handler ---
        function handleAIGenerate( field ) {
            if ( ! editorData.aiEnabled || ! editorData.ajaxUrl || ! editorData.aiNonce ) return;
            var setLoading = field === 'title' ? setAiTitleLoading : setAiDescLoading;
            setLoading( true );

            var formData = new FormData();
            formData.append( 'action', 'seovela_generate_ai_content' );
            formData.append( 'nonce', editorData.aiNonce );
            formData.append( 'type', field );
            formData.append( 'post_id', postId || 0 );
            formData.append( 'content', postContent.substring( 0, 3000 ) );
            formData.append( 'focus_keyword', focusKeyword );

            fetch( editorData.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
                .then( function( r ) { return r.json(); } )
                .then( function( data ) {
                    setLoading( false );
                    if ( data.success && data.data && data.data.content ) {
                        if ( field === 'title' ) {
                            updateMeta( '_seovela_meta_title', data.data.content );
                        } else {
                            updateMeta( '_seovela_meta_description', data.data.content );
                        }
                    }
                } )
                .catch( function() { setLoading( false ); } );
        }

        // --- Build schema type options ---
        var schemaOptions = [ { label: __( 'Auto-detect', 'seovela' ), value: 'auto' } ];
        if ( editorData.schemaTypes && editorData.schemaTypes.length ) {
            editorData.schemaTypes.forEach( function( t ) {
                schemaOptions.push( { label: t.label, value: t.value } );
            } );
        }

        // --- Render ---
        return el( Fragment, null,

            // Menu item
            el( PluginSidebarMoreMenuItem, { target: 'seovela-seo-sidebar', icon: seoIcon },
                __( 'Seovela SEO', 'seovela' )
            ),

            // Sidebar
            el( PluginSidebar, {
                name: 'seovela-seo-sidebar',
                title: __( 'Seovela SEO', 'seovela' ),
                icon: seoIcon
            },
                el( 'div', { className: 'seovela-gutenberg-sidebar', style: { padding: '0' } },

                    // ========== SEO Score ==========
                    el( Panel, null,
                        el( PanelBody, { title: __( 'SEO Score', 'seovela' ), initialOpen: true },
                            el( PanelRow, null,
                                el( 'div', { style: { width: '100%' } },
                                    el( SEOScoreRing, { score: seoScore } ),
                                    el( 'p', { style: { fontSize: '12px', color: '#64748b', margin: '4px 0 8px' } },
                                        __( 'Optimize your content for better search engine rankings', 'seovela' )
                                    ),
                                    el( 'div', { style: { display: 'flex', gap: '8px' } },
                                        el( Button, {
                                            variant: 'secondary', isSmall: true, isBusy: analysisLoading,
                                            disabled: analysisLoading,
                                            onClick: handleRefreshAnalysis,
                                            style: { flex: 1 }
                                        },
                                            analysisLoading
                                                ? el( Fragment, null, el( Spinner, null ), ' ' + __( 'Analyzing...', 'seovela' ) )
                                                : el( Fragment, null,
                                                    el( 'span', { className: 'dashicons dashicons-update', style: { fontSize: '16px', width: '16px', height: '16px', marginRight: '4px' } } ),
                                                    __( 'Refresh Analysis', 'seovela' )
                                                )
                                        ),
                                        analysisResults && el( Button, {
                                            variant: 'tertiary', isSmall: true,
                                            onClick: function() { setShowAnalysis( ! showAnalysis ); }
                                        },
                                            showAnalysis ? __( 'Hide', 'seovela' ) : __( 'View Analysis', 'seovela' )
                                        )
                                    ),
                                    showAnalysis && analysisLoading && el( 'div', { style: { textAlign: 'center', padding: '16px' } },
                                        el( Spinner, null )
                                    ),
                                    showAnalysis && ! analysisLoading && analysisResults && el( AnalysisResults, { results: analysisResults } )
                                )
                            )
                        )
                    ),

                    // ========== Focus Keyword ==========
                    el( Panel, null,
                        el( PanelBody, { title: __( 'Focus Keyword', 'seovela' ), initialOpen: true },
                            el( PanelRow, null,
                                el( 'div', { style: { width: '100%' } },
                                    el( TextControl, {
                                        value: focusKeyword,
                                        onChange: function( val ) { updateMeta( '_seovela_focus_keyword', val ); },
                                        placeholder: __( 'e.g., WordPress SEO plugin', 'seovela' )
                                    } ),
                                    editorData.aiEnabled && el( Button, {
                                        variant: 'secondary', isSmall: true, isBusy: keywordLoading,
                                        disabled: keywordLoading,
                                        onClick: handleSuggestKeywords,
                                        style: { marginTop: '4px' }
                                    },
                                        keywordLoading
                                            ? el( Fragment, null, el( Spinner, null ), ' ' + __( 'Suggesting...', 'seovela' ) )
                                            : el( Fragment, null,
                                                el( 'span', { className: 'dashicons dashicons-lightbulb', style: { fontSize: '16px', width: '16px', height: '16px', marginRight: '4px' } } ),
                                                __( 'Suggest Keywords', 'seovela' )
                                            )
                                    ),
                                    suggestions.length > 0 && el( 'div', { style: { marginTop: '8px' } },
                                        el( 'p', { style: { fontSize: '11px', color: '#64748b', margin: '0 0 4px' } },
                                            __( 'Click to use:', 'seovela' )
                                        ),
                                        el( 'div', { style: { display: 'flex', flexWrap: 'wrap', gap: '4px' } },
                                            suggestions.map( function( kw, i ) {
                                                return el( Button, {
                                                    key: i, isSmall: true, variant: 'tertiary',
                                                    onClick: function() {
                                                        updateMeta( '_seovela_focus_keyword', kw );
                                                        setSuggestions( [] );
                                                    },
                                                    style: {
                                                        background: '#f1f5f9', borderRadius: '4px', padding: '2px 8px',
                                                        fontSize: '12px', color: '#334155'
                                                    }
                                                }, kw );
                                            } )
                                        )
                                    ),
                                    el( 'p', { style: { fontSize: '11px', color: '#94a3b8', margin: '6px 0 0' } },
                                        __( 'Enter a keyword or phrase to optimize this content for search engines', 'seovela' )
                                    )
                                )
                            )
                        )
                    ),

                    // ========== Meta Title ==========
                    el( Panel, null,
                        el( PanelBody, { title: __( 'Meta Title', 'seovela' ), initialOpen: true },
                            el( PanelRow, null,
                                el( 'div', { style: { width: '100%' } },
                                    el( TextControl, {
                                        value: metaTitle,
                                        onChange: function( val ) { updateMeta( '_seovela_meta_title', val ); },
                                        placeholder: postTitle || __( 'Enter meta title...', 'seovela' ),
                                        maxLength: 70
                                    } ),
                                    el( CharacterCounter, { length: metaTitle.length, max: 60, warnStart: 30 } ),
                                    el( AIGenerateButton, { field: 'title', onGenerate: handleAIGenerate, loading: aiTitleLoading } )
                                )
                            )
                        )
                    ),

                    // ========== Meta Description ==========
                    el( Panel, null,
                        el( PanelBody, { title: __( 'Meta Description', 'seovela' ), initialOpen: true },
                            el( PanelRow, null,
                                el( 'div', { style: { width: '100%' } },
                                    el( TextareaControl, {
                                        value: metaDesc,
                                        onChange: function( val ) { updateMeta( '_seovela_meta_description', val ); },
                                        placeholder: __( 'Enter meta description...', 'seovela' ),
                                        rows: 3
                                    } ),
                                    el( CharacterCounter, { length: metaDesc.length, max: 160, warnStart: 70 } ),
                                    el( AIGenerateButton, { field: 'description', onGenerate: handleAIGenerate, loading: aiDescLoading } )
                                )
                            )
                        )
                    ),

                    // ========== Google Preview ==========
                    el( Panel, null,
                        el( PanelBody, { title: __( 'Google Preview', 'seovela' ), initialOpen: true },
                            el( PanelRow, null,
                                el( 'div', { style: { width: '100%' } },
                                    el( SERPPreview, { title: displayTitle, description: metaDesc, url: postLink } )
                                )
                            )
                        )
                    ),

                    // ========== Indexing Settings ==========
                    el( Panel, null,
                        el( PanelBody, { title: __( 'Indexing Settings', 'seovela' ), initialOpen: false },
                            el( PanelRow, null,
                                el( ToggleControl, {
                                    label: __( 'Noindex', 'seovela' ),
                                    help: noindex
                                        ? __( 'This page is hidden from search engines.', 'seovela' )
                                        : __( 'This page can be indexed by search engines.', 'seovela' ),
                                    checked: noindex,
                                    onChange: function( val ) { updateMeta( '_seovela_noindex', val ); }
                                } )
                            ),
                            el( PanelRow, null,
                                el( ToggleControl, {
                                    label: __( 'Nofollow', 'seovela' ),
                                    help: nofollow
                                        ? __( 'Search engines will not follow links on this page.', 'seovela' )
                                        : __( 'Search engines will follow links on this page.', 'seovela' ),
                                    checked: nofollow,
                                    onChange: function( val ) { updateMeta( '_seovela_nofollow', val ); }
                                } )
                            )
                        )
                    ),

                    // ========== Schema Markup ==========
                    el( Panel, null,
                        el( PanelBody, { title: __( 'Schema Markup', 'seovela' ), initialOpen: false },
                            el( PanelRow, null,
                                el( 'div', { style: { width: '100%' } },
                                    el( 'p', { style: { fontSize: '12px', color: '#64748b', margin: '0 0 8px' } },
                                        __( 'Structured data for rich search results', 'seovela' )
                                    ),
                                    el( ToggleControl, {
                                        label: __( 'Disable schema for this page', 'seovela' ),
                                        checked: disableSchema === 'yes',
                                        onChange: function( val ) { updateMeta( '_seovela_disable_schema', val ? 'yes' : '' ); }
                                    } ),
                                    disableSchema !== 'yes' && el( SelectControl, {
                                        label: __( 'Schema Type', 'seovela' ),
                                        value: schemaType,
                                        onChange: function( val ) { updateMeta( '_seovela_schema_type', val ); },
                                        options: schemaOptions
                                    } )
                                )
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
