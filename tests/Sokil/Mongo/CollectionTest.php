<?php

namespace Sokil\Mongo;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Database
     */
    private static $database;
    
    public static function setUpBeforeClass()
    {
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        self::$database = $client->getDatabase('test');
    }
    
    public static function tearDownAfterClass() {

    }
    
    public function testGetDocument()
    {
        // create document
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $document = $collection->createDocument(array('param' => 'value'));   
        $collection->saveDocument($document);
        
        // get document
        $foundDocument = $collection->getDocument($document->getId());
        
        $this->assertEquals($document->getId(), $foundDocument->getId());
    }
    
    public function testGetDocumentByStringId()
    {
        // create document
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $collection->delete();
        
        $document = $collection
            ->createDocument(array(
                '_id'   => 'abcdef',
                'param' => 'value'
            ));
        
        $document->save();
        
        // get document
        $foundDocument = $collection->getDocument('abcdef');
        
        $this->assertNotNull($foundDocument);
        
        $this->assertEquals($document->getId(), $foundDocument->getId());
    }
    
    public function testGetDocuments()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');
        
        // create document1
        $document1 = $collection->createDocument(array('param' => 'value1'));   
        $collection->saveDocument($document1);
        
        // create document 2
        $document2 = $collection->createDocument(array('param' => 'value2'));   
        $collection->saveDocument($document2);
        
        // get documents
        $foundDocuments = $collection->getDocuments(array(
            $document1->getId(),
            $document2->getId()
        ));
        
        $this->assertEquals(2, count($foundDocuments));
        
        $this->assertArrayHasKey((string) $document1->getId(), $foundDocuments);
        $this->assertArrayHasKey((string) $document2->getId(), $foundDocuments);
    }
    
    public function testSaveValidNewDocument()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');
        
        // create document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'required')
            )));
        
        $document->set('some-field-name', 'some-value');
        
        // save document
        $collection->saveDocument($document);
        
        $collection->delete();
    }
    
    public function testUpdateExistedDocument()
    {
        // create document
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $document = $collection->createDocument(array('param' => 'value'));   
        $collection->saveDocument($document);
        
        // update document
        $document->set('param', 'new-value');
        $collection->saveDocument($document);
        
        // test
        $document = $collection->getDocument($document->getId());
        $this->assertEquals('new-value', $document->param);
        
        $collection->delete();
    }
    
    /**
     * @expectedException \Sokil\Mongo\Document\Exception\Validate
     */
    public function testSaveInvalidNewDocument()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');
        
        // create document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'required')
            )));
        
        // save document
        
        $collection->saveDocument($document);
        
        $collection->delete();
    }
    
    public function testDeleteDocuments()
    {
        // get collection
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $collection->delete();
        
        // add
        $collection->createDocument(array('param' => 1))->save();
        $collection->createDocument(array('param' => 2))->save();
        $collection->createDocument(array('param' => 3))->save();
        $collection->createDocument(array('param' => 4))->save();
        
        // delete
        $collection->deleteDocuments($collection->expression()->whereGreater('param', 2));
        
        // test
        $this->assertEquals(2, count($collection));
    }
    
    public function testDeleteUnexistedColelction()
    {
        $collection = self::$database->getCollection('UNEXISTED_COLLECTION_NAME');
        $collection->delete();
    }
    
    public function testUpdateMultiple()
    {
        // get collection
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $collection->delete();
        
        // create documents
        $d1 = $collection->createDocument(array('p' => 1));
        $collection->saveDocument($d1);
        
        $d2 = $collection->createDocument(array('p' => 1));
        $collection->saveDocument($d2);
        
        // packet update
        $collection->updateMultiple(
            $collection->expression()->where('p', 1),
            $collection->operator()->set('k', 'v')
        );
        
        // test
        foreach($collection->find() as $document) {
            $this->assertArrayHasKey('k', $document->toArray());
        }
    }
    
    public function testUpdateMultipleOnEmptyExpression()
    {
        // get collection
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $collection->delete();
        
        // create documents
        $d1 = $collection->createDocument(array('p' => 1));
        $collection->saveDocument($d1);
        
        $d2 = $collection->createDocument(array('p' => 1));
        $collection->saveDocument($d2);
        
        // packet update
        $collection->updateAll(
            $collection->operator()->set('k', 'v')
        );
        
        // test
        foreach($collection->find() as $document) {
            $this->assertArrayHasKey('k', $document->toArray());
        }
    }
    
    public function testGetDistinct()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');
    
        // create documents
        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'A',
                )
            ))
            ->save();
        
        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'A',
                )
            ))
            ->save();
        
        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'B',
                )
            ))
            ->save();
        
        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F2',
                    'kk'    => 'C',
                )
            ))
            ->save();
        
        // get distinkt
        $distinctValues = $collection
            ->getDistinct('k.kk', $collection->expression()->where('k.f', 'F1'));
        
        $this->assertEquals(array('A', 'B'), $distinctValues);
    }
    
    public function testInsertMultiple()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection');
        
        $collection->insertMultiple(array(
            array('a' => 1, 'b' => 2),
            array('a' => 3, 'b' => 4),
        ));
        
        $document = $collection->find()->where('a', 1)->findOne();
        
        $this->assertNotEmpty($document);
        
        $this->assertEquals(2, $document->b);
    }
    
    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage ns not found
     */
    public function testValidateOnNotExistedCollection()
    {
        self::$database
            ->getCollection('phpmongo_unexisted_collection')
            ->validate(true);
    }
    
    public function testValidateOnExistedCollection()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection');
        
        $collection->createDocument(array('param' => 1))->save();
       
        $result = $collection->validate(true);
        
        $this->assertInternalType('array', $result);
    }
    
    public function testCappedCollectionInsert()
    {
        $collection = self::$database
            ->createCappedCollection('capped_collection', 3, 30);
        
        $collection->createDocument(array('param' => 1))->save();
        $collection->createDocument(array('param' => 2))->save();
        $collection->createDocument(array('param' => 3))->save();
        $collection->createDocument(array('param' => 4))->save();
        
        $this->assertEquals(3, $collection->find()->count());
        
        $documents = $collection->find();   
        
        $this->assertEquals(2, $documents->current()->param);
        
        $documents->next();
        $this->assertEquals(3, $documents->current()->param);
        
        $documents->next();
        $this->assertEquals(4, $documents->current()->param);
    }
    
    public function testStats()
    {
        $stats = self::$database
            ->createCollection('phpmongo_test_collection')
            ->stats();
        
        $this->assertEquals(1.0, $stats['ok']);
    }
    
    public function testAggregate()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->delete();
            
        $collection->createDocument(array('param' => 1))->save();
        $collection->createDocument(array('param' => 2))->save();
        $collection->createDocument(array('param' => 3))->save();
        $collection->createDocument(array('param' => 4))->save();
        
        $result = $collection->createPipeline()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')))
            ->aggregate();
        
        $this->assertEquals(9, $result[0]['sum']);
        
    }
    
    public function testExplainAggregate()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->delete();
            
        $collection->createDocument(array('param' => 1))->save();
        $collection->createDocument(array('param' => 2))->save();
        $collection->createDocument(array('param' => 3))->save();
        $collection->createDocument(array('param' => 4))->save();
        
        $pipelines = $collection->createPipeline()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')));
        
        try {
            $explain = $collection->explainAggregate($pipelines);
            $this->assertArrayHasKey('stages', $explain);
        } catch (\Exception $e) {
            $this->assertEquals('Explain of aggregation implemented only from 2.6.0', $e->getMessage());
        }
        
    }
}