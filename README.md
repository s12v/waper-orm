ORM with transparent vertical database partitioning
---------------------------------------------------

The main idea is to move variable-length fields to separate table, and leave only necessary fields in core table.
It allows to use covering indexes on core tables and keep high speed on large amounts of data without manual
query optimization.

Let's say, we have a table:
```
comment
id: int
userId: int
imageId: int
text: text
```

We will split it by two:
```
comment (fixed row-length table)
  id
  userId
  imageId
comment_data (variable length, "heavy" table)
  id
  text
```
Now we use only core tables in queries, and lazy-load data by ids from second table.

For example:
```
SELECT id FROM comment ORDER BY id LIMIT 12345678, 3
```
(This query is probably using covering index and will be executed very fast despite large number of rows)

Now we have ids, so let's fetch remaining fields:
```
SELECT id, userId, imageId FROM comment WHERE id IN (56, 57, 58)
SELECT id, text FROM comment_data WHERE id IN (56, 57, 58)
```
(This queries are very fast too)



Example:
--------

- Select all images by some criteria, ordered by RAND(), limit 10
- Find total rows count
- Select corresponding users
- Select corresponding comments, ordered by id

DB Scheme:
```
comment
  id, userId, imageId
comment_data
  id, text
image
  id, userId
image_data
  id, file, desc
user
  id
user_data
  id, name
```  

Code:
```php
$images = Model_Image_Table::getInstance()->findAll(
  array('id:<' => 4546456),
  array('userId', 'file'),
  array('rowCount' => true, 'limit' => '10', 'order' => new Waper_DB_SqlExpression("RAND()"))
);
if ($images) {
  $rowCount = Waper_DB::getInstance()->getTotalRowsCount();
  $users = $images->getUser(array("name"));
  $comments = $images->getComments(array("userId", "imageId", "text"), array('order' => array('id' => 'ASC')));
}
```
Generated queries:
```sql
SQL-connect
SELECT SQL_CALC_FOUND_ROWS `id`, `userId` FROM `image` WHERE (`id` < '4546456') ORDER BY RAND() LIMIT 10
SELECT FOUND_ROWS() as value
SELECT `id`, `file` FROM `image_data` WHERE `id` IN ('5', '9', '17', '8', '15', '11', '7', '30', '26', '13')
SELECT `id` FROM `user` WHERE `id` IN ('1', '2', '3')
SELECT `id`, `name` FROM `user_data` WHERE `id` IN ('1', '2', '3')
SELECT `id`, `userId`, `imageId` FROM `comment` WHERE (`imageId` IN ('5', '9', '17', '8', '15', '11', '7', '30', '26', '13')) ORDER BY `id` ASC
SELECT `id`, `text` FROM `comment_data` WHERE `id` IN ('1', '7', '10', '11', '15', '17', '20', '25', '26', '29', '34', '38', '41', '42', '47', '50', '51', '52', '53', '55', '62', '66', '67', '74', '75', '76', '80', '83', '89', '91', '92', '94', '97')
```