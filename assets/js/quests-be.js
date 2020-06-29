/**
 * Admin questerio script
 */

/* global __quest */

'use strict';

( $ => {

    let
        /**
         * Current position in the quest
         *
         * @type {{index: number, total: number}}
         * @private
         */
        __position = {
            qindex: 0,
            aindex: 0,
            qtotal: 1,
            atotal: 1
        };

        /**
         * Blank question entry value
         *
         * @private
         */
    const __blank = function(){
          return {
                  question    : '',
                  answers     : [{
                      answer      : '',
                      leads_to    : {
                          type    : 'question',
                          value   : '2'
                      }
                  }]
              }
    };

    let

        /**
         * No click event
         *
         * @param e
         * @returns {boolean}
         * @private
         */
        __noclick = function ( e ) {
            e.stopPropagation();
            e.preventDefault();
            return false;
        },

        /**
         * Update stats
         *
         * @private
         */
        __update_stats = function(){
            $( '.quest-question-wrap .stats .current'   ).html( __position.qindex + 1  );
            $( '.quest-question-wrap .stats .total'     ).html( __position.qtotal      );
            $( '.quest-answers-wrap .stats .current'    ).html( __position.aindex + 1  );
            $( '.quest-answers-wrap .stats .total'      ).html( __position.atotal      );
        },

        /**
         * Initialize array
         *
         * @private
         */
        __initialize = function () {
            let q = __quest.questions;
            __quest.questions = [];
            for( const k of Object.keys( q ) )
                __quest.questions.push( q[k] );
            if( ! __quest.questions.length )
                __quest.questions.push( __blank() );
            else {
                __position.qtotal = __quest.questions.length;
                __check_reset();
            }

        },

        /**
         * Load current question
         *
         * @private
         */
        __load_current = function () {
            __update_stats();
            let q = __quest.questions[ __position.qindex ];
            let a = q.answers[ __position.aindex ];
            tinymce.get( 'question' ).setContent( q.question );
            tinymce.get( 'answer' )  .setContent( a.answer );
            if( a.leads_to.type === 'question' ) {
                $('#answer_tab1').prop('checked', true);
                $('#leads_to').val( a.leads_to.value );
            } else {
                $('#answer_tab2').prop('checked', true);
                $('#leads_to_result').val( a.leads_to.value );
            }
        },

        /**
         * Save current question
         *
         * @private
         */
        __save_current = function () {
            __quest.questions[ __position.qindex ].question = tinymce.get( 'question' ).getContent();
            __quest.questions[ __position.qindex ].answers[ __position.aindex ].answer = tinymce.get( 'answer' ).getContent();
            if( $('#answer_tab1').prop('checked') ){
                __quest.questions[ __position.qindex ].answers[ __position.aindex ].leads_to.type = 'question';
                __quest.questions[ __position.qindex ].answers[ __position.aindex ].leads_to.value = $('#leads_to').val();
            } else {
                __quest.questions[ __position.qindex ].answers[ __position.aindex ].leads_to.type = 'result';
                __quest.questions[ __position.qindex ].answers[ __position.aindex ].leads_to.value = $('#leads_to_result').val();
            }
        },

        /**
         * Update main post form data
         */
        __update_post_form = function(){
            __save_current();
            $( '#quest-save-fields' ).remove();
            let _c = '<div id="quest-save-fields" style="display:none !important">' +
                '<input type="hidden" name="quest_save_fields" value="' + __quest.nonce + '" />';
            for( const [ i,q ] of __quest.questions.entries() ){
                _c += '<textarea name="question[]">' + q.question + '</textarea>';
                for( const a of q.answers )
                    _c += '<textarea name="question_' + i + '_answer[]">' + a.answer + '</textarea>' +
                        '<input type="hidden" name="question_' + i + '_answer_leads_to_type[]" value="'  + a.leads_to.type + '" />' +
                        '<input type="hidden" name="question_' + i + '_answer_leads_to_value[]" value="' + a.leads_to.value + '" />';
            }
            _c += '</div>';
            $( 'form#post' ).append( _c );
        },

        /**
         * Index type - rather it is an answer index and answer total or question ones
         * @param obj
         * @returns {{i: string, t: string}}
         * @private
         */
        __itype = function ( obj ) {
            let t = $( obj ).parents( '.quest-controls' ).last().data( 'type' );
            let ti = 'qindex', tt = 'qtotal';
            if( t === 'answer' ) { ti = 'aindex'; tt = 'atotal'; }
            return {
                i: ti,
                t: tt
            }
        };

        /**
         * Reset position on answers
         *
         * @param index
         * @private
         */
    const __check_reset = function( index ){
            if( 'aindex' === index ) return;
            __position.aindex = 0;
            __position.atotal = __quest.questions[ __position.qindex ].answers.length;
        };

    let

        /**
         * Decrease current index
         *
         * @param obj
         * @param first
         * @private
         */
        __dec = function ( obj, first ) {
            let t = __itype( obj );
            if ( first )
                __position[ t.i ] = 0;
            else if( __position[ t.i ] > 0 )
                __position[ t.i ]--;
            __check_reset( t.i );
        },

        /**
         * Increase current index
         *
         * @param obj
         * @param last
         * @private
         */
        __inc = function ( obj, last ) {
            let t = __itype( obj );
            if ( last )
                __position[ t.i ] = __position[ t.t ] - 1;
            else if( __position[ t.i ] < __position[ t.t ] - 1 )
                __position[ t.i ]++;
            __check_reset( t.i );
        },

        /**
         * Load first item
         *
         * @private
         */
        __load_first = function ( e ) {
            try {
                __save_current();
                __dec(this, true);
                __load_current();
            }catch( err ){
                console.log( err );
            }
            return __noclick( e );
        },

        /**
         * Load prev item
         *
         * @private
         */
        __load_prev = function ( e ) {
            try {
                __save_current();
                __dec(this);
                __load_current();
            }catch( err ){
                console.log( err );
            }
            return __noclick( e );
        },

        /**
         * Load next item
         *
         * @private
         */
        __load_next = function ( e ) {
            try{
                __save_current();
                __inc( this );
                __load_current();
            }catch( err ){
                console.log( err );
            }
            return __noclick( e );
        },

        /**
         * Load next item
         *
         * @private
         */
        __load_last = function ( e ) {
            try{
                __save_current();
                __inc( this, true );
                __load_current();
            }catch( err ){
                console.log( err );
            }
            return __noclick( e );
        },

        /**
         * Make a deep copy of the object
         *
         * @param original
         * @returns {any}
         * @private
         */
        __copy = function( original ){
            return JSON.parse( JSON.stringify( original ) );
        },

        /**
         * Create new item
         *
         * @private
         */
        __new_item = function ( obj, after, copy ) {
            __save_current();
            let ii  = ( after ? 1 : -1 );
            let t   = __itype( obj ), ci = -1, ni;
            if( copy )
                ci = __position[t.i];
            __position[t.i]+=ii;
            if( __position[t.i] >= __position[t.t] ) {
                if( t.i === 'aindex' ) {
                    ni =  __blank().answers[0];
                    if( ci >= 0 )
                        ni =  __copy( __quest.questions[__position.qindex].answers[ ci ] );
                    __quest.questions[__position.qindex].answers.push( ni );
                }else {
                    ni = __blank();
                    if( ci >= 0 )
                        ni = __copy( __quest.questions[ci] );
                    __quest.questions.push( ni );
                }
            }else{
                if( __position[t.i] < 0 ) __position[t.i] = 0;
                if ( t.i === 'aindex' ){
                    ni = __blank().answers[0];
                    if( ci >= 0 )
                        ni = __copy( __quest.questions[__position.qindex].answers[ ci ] );
                    __quest.questions[__position.qindex].answers.splice(
                        __position[t.i], 0, ni
                    );
                } else {
                    ni = __blank();
                    if( ci >= 0 )
                        ni = __copy( __quest.questions[ci] );
                    __quest.questions.splice(
                        __position[t.i], 0, ni
                    );
                }
            }
            __position[t.t]++;
            __check_reset( t.i );
            __load_current();
        },

        /**
         * Move current item
         *
         * @private
         */
        __move_item = function ( obj, right ) {
            let ii  = ( right ? 1 : -1 );
            let t   = __itype( obj );
            if( __position[ t.i ] + ii < 0 || __position[ t.i ] + ii >= __position[ t.t ]  ) return;
            __save_current();
            let ci = __position[ t.i ], c;
            __position[ t.i ] += ii;
            if( t.i === 'aindex' ) {
                c  = __quest.questions[__position.qindex].answers[ci];
                __quest.questions[__position.qindex].answers[ci] =
                    __quest.questions[__position.qindex].answers[__position[t.i]];
                __quest.questions[__position.qindex].answers[__position[t.i]] = c;
            }
            else {
                c  = __quest.questions[ci];
                __quest.questions[ci] = __quest.questions[__position[t.i]];
                __quest.questions[__position[t.i]] = c;
            }
            __check_reset( t.i );
            __load_current();
        },

        /**
         * Add new item after the current one
         *
         * @param e
         * @returns {boolean}
         * @private
         */
        __add_new_after = function( e ){
            try{
                __new_item( this, true );
            }catch( err ){
                console.log( err );
            }
            return __noclick( e );
        },

        /**
         * Add new item before current one
         *
         * @private
         */
        __add_new_before = function ( e ) {
            try{
                __new_item( this, false );
            }catch( err ){
                console.log( err );
            }
            return __noclick( e );
        },

        /**
         * Move current item to the left
         *
         * @param e
         * @private
         */
        __move_left = function ( e ) {
            try{
                __move_item( this, false );
            }catch( err ){
                console.log( err );
            }
            return __noclick( e );
        },

        /**
         * Move current item to the right
         *
         * @private
         */
        __move_right = function ( e ) {
            try{
                __move_item( this, true );
            }catch( err ){
                console.log( err );
            }
            return __noclick( e );
        },

        /**
         * Copy current to new element
         *
         * @param e
         * @returns {boolean}
         * @private
         */
        __copy_to_new = function( e ){
            try{
                __new_item( this, true, true );
            }catch( err ){
                console.log( err );
            }
            return __noclick( e );
        },

        /**
         * Remove current item
         *
         * @param e
         * @returns {boolean}
         * @private
         */
        __remove = function ( e ) {
            try{
                let t   = __itype( this );
                if( __position[ t.t ] > 1 ){
                    if( t.i === 'aindex' )
                        __quest.questions[__position.qindex].answers.splice( __position[ t.i ], 1 );
                    else
                        __quest.questions.splice( __position[ t.i ], 1 );
                    __position[ t.t ]--;
                    __position[ t.i ]-= ( __position[ t.i ] > 0 ? 1 : 0 );
                }else{
                    if( t.i === 'aindex' )
                        __quest.questions[__position.qindex].answers[ __position[ t.i ] ] = __blank().answers[0];
                    else
                        __quest.questions[__position[ t.i ] ] = __blank();
                }
                __check_reset( t.i );
                __load_current();
            }catch( err ){
                console.log( err );
            }
            return __noclick( e );
        },

        __assign = function () {
            $( '.quest-first'       ).off().click( __load_first     );
            $( '.quest-last'        ).off().click( __load_last      );
            $( '.quest-next'        ).off().click( __load_next      );
            $( '.quest-prev'        ).off().click( __load_prev      );
            $( '.quest-add-after'   ).off().click( __add_new_after  );
            $( '.quest-add-before'  ).off().click( __add_new_before );
            $( '.quest-copy-to-new' ).off().click( __copy_to_new    );
            $( '.quest-move-left'   ).off().click( __move_left      );
            $( '.quest-move-right'  ).off().click( __move_right     );
            $( '.quest-remove'      ).off().click( __remove         );
            // Update before saving
            $( 'form#post' ).on( 'submit', __update_post_form );
            __initialize();
            setTimeout( __load_current, 500 );
        }

    return {

        init : function(){
            $( document ).ready( __assign );
        }

    }
} )(jQuery).init();
