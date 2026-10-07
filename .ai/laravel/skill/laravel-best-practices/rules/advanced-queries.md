# Advanced Query Best Practices

## Select a Single Relationship Value with a Subquery

When only one value from a has-many relationship is needed, use a correlated subquery in `addSelect()` instead of loading the collection. Add `withCasts()` for the selected column.

```php
$query->addSelect([
    'last_login_at' => Login::select('created_at')
        ->whereColumn('user_id', 'users.id')->latest()->take(1),
])->withCasts(['last_login_at' => 'datetime']);
```

## Expose the Subquery as a `belongsTo`

Select the foreign key (`last_login_id`) with the same pattern, define `belongsTo(Login::class, 'last_login_id')`, and `with('lastLogin')`. This still runs a second query but avoids loading the whole has-many collection.

## Combine Counts with Conditional Aggregates

Several counts over the same data set can be one query using `count(case when ... then 1 end)` in `selectRaw()`. Use `toBase()` when scalars suffice and hydration adds nothing. Check expression syntax against the application's database engine.

## Reuse Loaded Parents with `setRelation()`

When children are loaded from a parent and code reads `$child->parent`, call `$parent->comments->each->setRelation('feature', $parent)` to avoid a lazy query per child.

## Compare `whereHas()` with an `IN` Subquery

`whereHas()` usually yields `EXISTS`; `whereIn('company_id', Company::where(...)->select('id'))` yields `IN`. Either may be faster depending on engine, indexes, and cardinality, so measure. Neither loads results into PHP.

## Measure Two Simple Queries Against One Complex Query

Two targeted queries can beat one complex correlated subquery or join when the first is highly selective. They cost an extra round trip, may transfer a large ID list, and give no single-query consistency snapshot. Decide from query plans.

## Design Composite Indexes for the Query

For common multi-column sorts, consider a composite index such as `['last_name', 'first_name']` ordered to support filters and sorting. Matching the `ORDER BY` list does not guarantee use; engines may merge indexes or sort explicitly. Verify the plan.

## Correlated Subquery for Has-Many Ordering

Joining a has-many table to sort can duplicate parent rows unless reduced to one row per parent. A correlated subquery in `orderByDesc(Login::select('created_at')->whereColumn('user_id', 'users.id')->latest()->take(1))` is usually simpler; performance depends on the plan and indexes.
