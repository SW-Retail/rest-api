# A simple  SW-Retail REST api PHP client

This file provides a simple client for the SW-Retail REST-API. For more information, visit https:/www.swretail.nl

There is only one dependency for this to run, and that is you need to have PHP cURL. 

# Examples

## Setup 
Include the swretailapi.php file in your project

Instantiate the object with the proper credentials
```php
$api=new mySWRestAPI('myswretailcloudinstance','myswretailusername','myswretailpassword');
```
and you are good to go.

All examples use print_r to display the result code of the operation. 

## Lookup an article id when you only have a barcode
```php
print_r($api->articleIDfromBarcode(139735));
```


## Update article information
This u pdates article_id 10 and sets the article_memo field to something
```php
$data=['article_id'=>10,'article_memo'=>'something'];
print_r($api->putArticle($data));
```

## Update the article memo based on a barcode.
Beware -> this costs two calls, one lookup, one update
```php
 $data=['article_id'=>$api->articleIDfromBarcode(139735),'article_memo'=>'something'];
print_r($api->putArticle($data));
```

## Get a list of all warehouses in the system
```php
print_r($api->getWarehouses());
```

## Create an article
remember to save the article_id that you'll get back!
```php
$new_article=['article_description'=>'My very own article!'];
print_r($api->postArticle($new_article));
```

## Delete an article
If it is not possible to delete the article you get an error message back. 
 ```php
 print_r($api->deleteArticle(10));
 ```

## Get stock information of an article
Get the stock in warehouse 1, position 1 on the sizeruler, for article 10) with a get request. If you do not use sizerulers then the position is always 1
```php
print_r($api->getArticle_Stock(10,2,1))
```

## Upload an image to an article
```php
print_r($api->uploadArticleImage(1,"c:/directory/image.jpg","my nice image"));
```

# List of all endpoints 
Use the documentation of the REST api to find all endpoints that are available

