# SW-Retail rest-api examples

## Setup 
Include the swretailapi.php file in your project

Instantiate the object with the proper credentials
```
$api=new mySWRestAPI('mycloud','swretail','password');
```

## lookup an article id when you only have a barcode
```
print_r($api->articleIDfromBarcode(139735));
```


## Update article information
This u pdates article_id 10 and sets the article_memo field to something
```
$data=['article_id'=>10,'article_memo'=>'something'];
print_r($api->putArticle($data));
```

## Update the article memo based on a barcode.
Beware -> this costs two calls, one lookup, one update
```
 $data=['article_id'=>$api->articleIDfromBarcode(139735),'article_memo'=>'something'];
print_r($api->putArticle($data));
```

## Get a list of all warehouses in the system
```
print_r($api->getWarehouses());
```

## Create an article
remember to save the article_id that you'll get back!
```
$new_article=['article_description'=>'My very own article!'];
print_r($api->postArticle($new_article));
```

## Delete an article
 ```
 print_r($api->deleteArticle(10));
 ```

## Get stock information of an article
Get the stock in warehouse 1, position 1 on the sizeruler, for article 10) with a get request. If you do not use sizerulers then the position is always 1
```
print_r($api->getArticle_Stock(10,2,1))
```

## Upload an image to an article
```
$api->uploadArticleImage(1,"c:/directory/image.jpg","my nice image");
```

# List of all endpoints 
Use the documentation of the REST api to find all endpoints that are available

