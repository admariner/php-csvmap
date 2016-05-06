<?php

use Keboola\CsvMap\Mapper;

class MapperTest extends PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true
                ]
            ],
            'user.id' => [
                'type' => 'column',
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

    public function testParseShorthand()
    {
        $config = [
            'id' => 'id',
            'timestamp' => 'timestamp',
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $file = $parser->getCsvFiles()['root'];

        $expected = <<<CSV
"id","timestamp"
"1","1234567890"\n
CSV;
        $this->assertEquals($expected, file_get_contents($file->getPathname()));
    }

    public function testParseShorthandWithRelation()
    {
        $config = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'pk',
                    'primaryKey' => true,
                ]
            ],
            'timestamp' => 'timestamp',
            'reactions' => [
                'type' => 'table',
                'destination' => 'reactions',
                'tableMapping' => [
                    'user.id' => 'id',
                    'user.username' => 'username',
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);

        $file1 = $parser->getCsvFiles()['root'];
        $expected1 = <<<CSV
"pk","timestamp"
"1","1234567890"\n
CSV;
        $this->assertEquals($expected1, file_get_contents($file1->getPathname()));


        $file2 = $parser->getCsvFiles()['reactions'];

        $expected2 = <<<CSV
"id","username","root_pk"
"456","jose","1"
"789","mike","1"\n
CSV;
        $this->assertEquals($expected2, file_get_contents($file2->getPathname()));
    }

    public function testParseNoPK()
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id'
                ]
            ],
            'user.id' => [
                'type' => 'column',
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
                ]
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
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true
                ]
            ],
            'user.id' => [
                'type' => 'column',
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
                ]
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

    public function testParentKeyPK()
    {
        $config = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
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
                            'destination' => 'user_id',
                            'primaryKey' => true
                        ]
                    ]
                ],
                'parentKey' => [
                    'primaryKey' => true
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['user_id', 'root_pk'], $result['post_reactions']->getPrimaryKey(true));
    }

    public function testParentKeyDestination()
    {
        $config = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
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
                            'destination' => 'user_id',
                            'primaryKey' => true
                        ]
                    ]
                ],
                'parentKey' => [
                    'destination' => 'post_id'
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals('"user_id","post_id"' . PHP_EOL, file($result['post_reactions'])[0]);
    }

    public function testEmptyArray()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'arr' => [
                'type' => 'table',
                'destination' => 'children',
                'tableMapping' => [
                    'child_id' => [
                        'mapping' => [
                            'destination' => 'child_id'
                        ]
                    ]
                ]
            ]
        ];

        $data = [
            (object) [
                'id' => 1
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['"id","children"' . PHP_EOL, '"1",""' . PHP_EOL], file($result['root']));
    }

    public function testEmptyString()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'str' => [
                'mapping' => [
                    'destination' => 'text'
                ]
            ]
        ];

        $data = [
            (object) [
                'id' => 1,
                'str' => 'asdf'
            ],
            (object) [
                'id' => 2
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","text"' . PHP_EOL,
                '"1","asdf"' . PHP_EOL,
                '"2",""' . PHP_EOL
            ],
            file($result['root'])
        );
    }

    public function testPrimaryKey()
    {
        $config = [
            'timestamp' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'timestamp'
                ]
            ],
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id',
                    'primaryKey' => true
                ]
            ],
            'user.id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'user_id'
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['post_id'], $result['root']->getPrimaryKey(true));
    }

    /**
     * @expectedException \Keboola\CsvMap\Exception\BadConfigException
     * @expectedExceptionMessage Key 'mapping.destination' is not set for column 'timestamp'.
     */
    public function testNoMappingKeyColumn()
    {

        $config = [
            'timestamp' => [
                'type' => 'column'
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
    }

    /**
     * @expectedException \Keboola\CsvMap\Exception\BadConfigException
     * @expectedExceptionMessage Key 'destination' is not set for table 'arr'.
     */
    public function testNoDestinationTable()
    {

        $config = [
            'arr' => [
                'type' => 'table'
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
    }

    /**
     * @expectedException \Keboola\CsvMap\Exception\BadConfigException
     * @expectedExceptionMessage Key 'tableMapping' is not set for table 'reactions'.
     */
    public function testNoTableMapping()
    {
        $config = [
            'reactions' => [
                'type' => 'table',
                'destination' => 'children'
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
    }

    /**
     * @expectedException \Keboola\CsvMap\Exception\BadConfigException
     * @expectedExceptionMessage Key 'destination' is not set for table 'reactions'.
     */
    public function testNoDestinationNestedTable()
    {
        $config = [
            'reactions' => [
                'type' => 'table',
                'tableMapping' => [
                    'child_id' => [
                        'mapping' => [
                            'destination' => 'child_id'
                        ]
                    ]
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
    }

    public function testDataInjection()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'userData' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'userCol'
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data, ['userData' => 'blah']);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","userCol"' . PHP_EOL,
                '"1","blah"' . PHP_EOL
            ],
            file($result['root'])
        );
    }

    public function testDataInjectionPK()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id',
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
                ]
            ],
            'userData' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'userCol',
                    'primaryKey' => true
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data, ['userData' => 'blah']);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","userCol"' . PHP_EOL,
                '"1","blah"' . PHP_EOL
            ],
            file($result['root'])
        );
        $this->assertEquals(['id','userCol'], $result['root']->getPrimaryKey(true));

        $this->assertEquals(
            [
                '"user_id","root_pk"' . PHP_EOL,
                '"456","1,blah"' . PHP_EOL,
                '"789","1,blah"' . PHP_EOL
            ],
            file($result['post_reactions'])
        );
    }

    public function testDataInjectionNoData()
    {
        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'userData' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'userCol'
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(
            [
                '"id","userCol"' . PHP_EOL,
                '"1",""' . PHP_EOL
            ],
            file($result['root'])
        );
    }

    public function testUserDataPropagation()
    {
        $data = $this->getSampleData();

        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'user' => [
                'type' => 'table',
                'destination' => 'users',
                'tableMapping' => [
                    'id' => [
                        'mapping' => [
                            'destination' => 'id',
                            'primaryKey' => true
                        ]
                    ],
                    'username' => [
                        'mapping' => [
                            'destination' => 'username'
                        ]
                    ],
                    'keboola_source' => [
                        'type' => 'user',
                        'mapping' => [
                            'destination' => 'keboola_source'
                        ]
                    ]
                ],
                'parentKey' => [
                    'disable' => true
                ]
            ],
            'user.id' => [
                'mapping' => [
                    'destination' => 'user_id'
                ]
            ],
            'keboola_source' => [
                'type' => 'user',
                'mapping' => [
                    'destination' => 'keboola_source'
                ]
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data, [
            'keboola_source' => 'search',
        ]);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['"id","user_id","keboola_source"' . PHP_EOL, '"1","123","search"' . PHP_EOL], file($result['root']));
        $this->assertEquals(['"id","username","keboola_source"' . PHP_EOL, '"123","alois","search"' . PHP_EOL], file($result['users']));
    }

    public function testObjectToTable()
    {
        $data = $this->getSampleData();

        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'id'
                ]
            ],
            'user' => [
                'type' => 'table',
                'destination' => 'users',
                'tableMapping' => [
                    'id' => [
                        'mapping' => [
                            'destination' => 'id',
                            'primaryKey' => true
                        ]
                    ],
                    'username' => [
                        'mapping' => [
                            'destination' => 'username'
                        ]
                    ]
                ],
                'parentKey' => [
                    'disable' => true
                ]
            ],
            'user.id' => [
                'mapping' => [
                    'destination' => 'user_id'
                ]
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['"id","user_id"' . PHP_EOL, '"1","123"' . PHP_EOL], file($result['root']));
        $this->assertEquals(['"id","username"' . PHP_EOL, '"123","alois"' . PHP_EOL], file($result['users']));
    }

    public function testDisableParentKey()
    {

        $config = [
            'id' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'post_id'
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
                'parentKey' => [
                    'disable' => true
                ]
            ]
        ];

        $data = $this->getSampleData();

        $parser = new Mapper($config);
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals(['"post_id"' . PHP_EOL, '"1"' . PHP_EOL], file($result['root']));
        $this->assertEquals(['"user_id"' . PHP_EOL, '"456"' . PHP_EOL, '"789"' . PHP_EOL], file($result['post_reactions']));
    }

    public function testChildSameParser()
    {
        $data = [
            (object) [
                'id' => 1,
                'child' => (object) [
                    'id' => 1.1
                ],
                'arrChild' => [ // redundant?
                    (object) ['id' => '1.2']
                ]
            ]
        ];

        $config = [
            'id' => [
                'mapping' => [
                    'destination' => 'post_id'
                ]
            ],
            'child' => [
                'type' => 'table',
                'destination' => 'items',
                'parentKey' => [
                    'disable' => true
                ]
            ],
            'arrChild' => [
                'type' => 'table',
                'destination' => 'items',
                'parentKey' => [
                    'disable' => true
                ]
            ]
        ];

        $parser = new Mapper($config, 'items');
        $parser->parse($data);
        $result = $parser->getCsvFiles();

        $this->assertEquals([
            '"post_id"' . PHP_EOL,
            '"1.1"' . PHP_EOL,
            '"1.2"' . PHP_EOL,
            '"1"' . PHP_EOL
        ], file($result['items']));
    }

    public function testArrayItemToColumn()
    {
        $config = [
            'arr.0' => [
                'mapping' => [
                    'destination' => 'first_arr_item'
                ]
            ]
        ];

        $data = [
            (object) [
                'arr' => [
                    'one', 'two'
                ]
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($data);

        $this->assertEquals(['"first_arr_item"' . PHP_EOL, '"one"' . PHP_EOL], file($parser->getCsvFiles()['root']));
    }

    /**
     * @expectedException \Keboola\CsvMap\Exception\BadDataException
     * @expectedExceptionMessage Error writing 'user' column: Cannot write object into a column
     */
    public function testObjectToColumnError()
    {
        $config = [
            'user' => [
                'mapping' => [
                    'destination' => 'user'
                ]
            ]
        ];

        $parser = new Mapper($config);
        $parser->parse($this->getSampleData());
    }

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
