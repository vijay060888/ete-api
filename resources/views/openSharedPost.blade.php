<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$postTitle}}</title>

    <meta property="og:title" content="{{$postTitle}}" />
    <meta property="og:description" content="{{$postDescription}}" />
    <meta property="og:image" content="{{$postImage}}" />
</head>
<body>
    <script>
        const urlToOpen = "nxtgov://deepLink/post/{{$postId}}";
        
        window.onload = function () {
            if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                window.location.href = urlToOpen;
            } else {
                alert("You need a mobile app to view this post");
            }
        };
    </script>
</body>
</html>
