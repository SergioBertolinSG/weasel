<?php

namespace App\API\DesignDocuments;

class MeasurementDesignDocument implements \Doctrine\CouchDB\View\DesignDocument
{
    public function getData()
    {
        return array(
            'language' => 'javascript',
            'views' => array(
                'by_hash' => array(
                    'map' => 'function(doc) {
                        if(\'measurement\' == doc.type) {
                            emit(doc.hash, doc);
                        }
                    }',
                    'reduce' => '_count'
                ),
            ),
        );
    }
}