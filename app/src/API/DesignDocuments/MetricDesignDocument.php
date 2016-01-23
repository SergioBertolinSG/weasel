<?php

namespace App\API\DesignDocuments;

class MetricDesignDocument implements \Doctrine\CouchDB\View\DesignDocument
{
    public function getData()
    {
        return array(
            'language' => 'javascript',
            'views' => array(
                'by_hash' => array(
                    'map' => 'function(doc) {
                        if(\'metric\' == doc.type) {
                            emit(doc.hash, doc._id);
                        }
                    }',
                    'reduce' => '_count'
                ),
            ),
        );
    }
}