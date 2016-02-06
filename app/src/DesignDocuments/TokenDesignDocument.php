<?php

namespace App\DesignDocuments;

class TokenDesignDocument implements \Doctrine\CouchDB\View\DesignDocument
{
    public function getData()
    {
        return array(
            'language' => 'javascript',
            'views' => array(
                'by_token' => array(
                    'map' => 'function(doc) {
                        if(\'token\' == doc.type) {
                            emit(doc.token, doc);
                        }
                    }',
                    'reduce' => '_count'
                ),
            ),
        );
    }
}