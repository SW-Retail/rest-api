# SW-Retail REST API PHP client

This file provides a simple client for the SW-Retail REST-API in PHP. For more information about SW-Retail, visit https:/www.swretail.nl. 

We have kept the amount of files to an absolute minimum (1) to make integration in your project as easy as possible.

There is only one dependency for this to run, and that is you need to have PHP cURL. 

# Usage
The client supports most calls of the API via "magic" functions. The function name is the endpoint name, prefixed with the operation (put, get, post, delete) 

So, for example, in order to access the article endpoint you have four functions in the client
~~~php
function getArticle($array_with_url_fields)
function deleteArticle($array_with_url_fields)
function putArticle($array_with_put_fields)     
function postArticle($array_with_post_fields)
~~~
All these functions return an array with the return state of the call to the endpoint. 


# Examples

## Setup 
Include the swretailapi.php file in your project

Instantiate the object with the proper credentials      (get an api key on this location:  Modules - Settings - Employees - API Keys )
```php
$api=new mySWRestAPI('myswretailcloudinstance','apikey');
```
and you are good to go.

All examples use print_r to display the result code of the operation. 

## Update article information
This updates article_id 10 and sets the article_memo field to something. You can add extra article fields to update at once. 
```php
$data=['article_id'=>10,'article_memo'=>'something'];
print_r($api->putArticle($data));
```

## Get a list of all warehouses in the system
```php
print_r($api->getWarehouses());
```

## Create an article
Remember to save the article_id that you'll get back! You can use all fields as specified in the documentation.
```php
$new_article=['article_description'=>'My very own article!'];
print_r($api->postArticle($new_article));
```

## Delete an article
Delete article id 10. If it is not possible to delete the article you get an error message back. 
 ```php
 print_r($api->deleteArticle(10));
 ```

## Get stock information of an article
Get the stock in warehouse 1, position 1 on the sizeruler, for article 10) with a get request. If you do not use sizerulers then the position is always 1
```php
print_r($api->getArticle_Stock(10,2,1))
```

## Upload an image to an article
Make sure the image does not exceed the size limits. This uploads an image to article id 10
```php
print_r($api->uploadArticleImage(10,"c:/directory/niceimage.jpg","my nice image description"));
```

## Lookup an article id when you only have a barcode
This function is not a direct api function but has some extra code in the client. 
```php
print_r($api->articleIDfromBarcode("1234567890123"));
```

## Update the article memo based on a barcode
Beware -> this costs two calls, one lookup for the barcode, one update
```php
 $data=['article_id'=>$api->articleIDfromBarcode("1234567890123"),'article_memo'=>'something'];
print_r($api->putArticle($data));
```

## Get a list of articles that have changed in the last 10 minutes: 
Gets a list of ids so you know what articles to update
```php
print_r($api->getArticle_changed(1000));
```
# List of all endpoints 
Use the documentation of the REST api to find all endpoints that are available

