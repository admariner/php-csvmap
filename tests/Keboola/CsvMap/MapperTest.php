<?php

use Keboola\CsvMap\Mapper;

class MapperTest extends PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        $config = [
            'timestamp' => [
                'type' => 'column', // default?
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column', // default?
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true
                ]
            ],
            'user.id' => [
                'type' => 'column', // default?
                'mapping' => [
                    'destination' => 'user_id'
                ]
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user/id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id'
                        ],
                        'delimiter' => '/'
                    ]
                ],
//                 'parentKey' => [
//                     'primaryKey' => true,
//                     //'columns' => ['id', 'user_id'],
//                     //'hash' => true
//                 ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['root', 'post_reactions'], array_keys($result));
        foreach($result as $name => $file) {
            $this->assertFileEquals('./tests/data/' . $name, $file->getPathname());
        }
    }

    public function testParseNoPK()
    {

        $config = [
            'timestamp' => [
                'type' => 'column', // default?
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column', // default?
                'mapping' => [
                    'destination' => 'post_id'
                ]
            ],
            'user.id' => [
                'type' => 'column', // default?
                'mapping' => [
                    'destination' => 'user_id'
                ]
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id'
                        ]
                    ]
                ],
//                 'parentKey' => [
//                     'primaryKey' => true,
//                     //'columns' => ['id', 'user_id'],
//                     //'hash' => true
//                 ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        foreach($result as $name => $file) {
            $this->assertFileEquals('./tests/data/noPK/' . $name, $file->getPathname());
        }
    }

    public function testParseCompositePK()
    {

        $config = [
            'timestamp' => [
                'type' => 'column', // default?
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column', // default?
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true
                ]
            ],
            'user.id' => [
                'type' => 'column', // default?
                'mapping' => [
                    'destination' => 'user_id',
                    'primaryKey' => true
                ]
            ],
            'reactions' => [
                'type' => 'table',
                'destination' => 'post_reactions',
                'tableMapping' => [
                    'user.id' => [
                        'type' => 'column',
                        'mapping' => [
                            'destination' => 'user_id'
                        ]
                    ]
                ],
//                 'parentKey' => [
//                     'primaryKey' => true,
//                     //'columns' => ['id', 'user_id'],
//                     //'hash' => true
//                 ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        foreach($result as $name => $file) {
            $this->assertFileEquals('./tests/data/compositePK/' . $name, $file->getPathname());
        }
    }

    // TODO test more cols PK

    protected function getSampleData()
    {
        return [
            (object) [
                'timestamp' => 1234567890,
                'id' => 1,
                'text' => 'asdf',
                'user' => (object) [
                    'id' => 123,
                    'username' => 'alois'
                ],
                'reactions' => [
                    (object) [
                        'user' => (object) [
                            'id' => 456,
                            'username' => 'jose'
                        ]
                    ],
                    (object) [
                        'user' => (object) [
                            'id' => 789,
                            'username' => 'mike'
                        ]
                    ]
                ]
            ]
        ];
    }
}