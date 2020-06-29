/**
 * Front questerio script
 */

/* global __quest */

'use strict';

( $ => {

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

        __position = 0,

        /**
         * Load current question
         *
         * @private
         */
        __load_current = function () {
            let q = __quest.questions[ __position ], ac = '';
            if( q.answers.length )
                for( const a of q.answers )
                    ac += '<div  class="quest-answer" ' +
                                'data-leadtype="' + a.leads_to.type + '" ' +
                                'data-leadto ="' + a.leads_to.value + '" ' +
                                'data-leadurl="' + a.leads_to.link +  '">' + a.answer + '</div>';
            $( '#quest-content' ).html(
                '<div class="quest-question">' + q.question + '</div>' +
                '<div class="quest-answers">' + ac + '</div>'
            );
            __assign();
        },

        __start = function () {
            __position = 0;
            __load_current();
        },

        __next = function () {
            if( 'question' !== $( this ).data( 'leadtype' ) )
                window.location.href = $( this ).data( 'leadurl' );
            else{
                __position = Number( $( this ).data( 'leadto' ) ) - 1;
                __load_current();
            }
        };

        const __assign = function () {
            console.log( 'Questerio initialize..' );
            $( '#quest-start'  ).off().click(  __start  );
            $( '.quest-answer' ).off().click( __next    );
        }

    return {

        init : function(){
            $( document ).ready( __assign );
        }

    }
} )(jQuery).init();
