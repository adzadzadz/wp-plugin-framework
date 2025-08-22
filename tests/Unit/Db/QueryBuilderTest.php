<?php

namespace Tests\Unit\Db;

use PHPUnit\Framework\TestCase;
use AdzWP\Db\QueryBuilder;

/**
 * Unit tests for the QueryBuilder class
 */
class QueryBuilderTest extends TestCase
{
    private QueryBuilder $queryBuilder;
    
    protected function setUp(): void
    {
        $this->queryBuilder = new QueryBuilder();
    }
    
    public function testSelectMethod()
    {
        $qb = $this->queryBuilder->select(['id', 'name', 'email']);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
        
        // Test fluent interface
        $qb2 = $qb->select('status');
        $this->assertSame($qb, $qb2);
    }
    
    public function testFromMethod()
    {
        $qb = $this->queryBuilder->from('users');
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
    
    public function testWhereMethod()
    {
        $qb = $this->queryBuilder->where('id', '=', 1);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
        
        // Test without operator (defaults to =)
        $qb2 = $this->queryBuilder->where('status', 'active');
        $this->assertInstanceOf(QueryBuilder::class, $qb2);
    }
    
    public function testWhereInMethod()
    {
        $qb = $this->queryBuilder->whereIn('id', [1, 2, 3]);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
    
    public function testOrderByMethod()
    {
        $qb = $this->queryBuilder->orderBy('created_at', 'DESC');
        $this->assertInstanceOf(QueryBuilder::class, $qb);
        
        // Test default direction (ASC)
        $qb2 = $this->queryBuilder->orderBy('name');
        $this->assertInstanceOf(QueryBuilder::class, $qb2);
    }
    
    public function testLimitMethod()
    {
        $qb = $this->queryBuilder->limit(10);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
    
    public function testOffsetMethod()
    {
        $qb = $this->queryBuilder->offset(20);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
    
    public function testJoinMethod()
    {
        $qb = $this->queryBuilder->join('profiles', 'users.id', '=', 'profiles.user_id');
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
    
    public function testLeftJoinMethod()
    {
        $qb = $this->queryBuilder->leftJoin('profiles', 'users.id', '=', 'profiles.user_id');
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
    
    public function testGroupByMethod()
    {
        $qb = $this->queryBuilder->groupBy('status');
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
    
    public function testHavingMethod()
    {
        $qb = $this->queryBuilder->having('COUNT(*)', '>', 1);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
    
    public function testFluentChaining()
    {
        $qb = $this->queryBuilder
            ->select(['id', 'name'])
            ->from('users')
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(10);
            
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
}