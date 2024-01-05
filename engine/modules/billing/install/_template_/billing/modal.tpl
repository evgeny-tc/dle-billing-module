<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Биллинг</title>

    <link media="screen" href="/templates/{module.skin}/billing/css/styles.css?v=2" type="text/css" rel="stylesheet" />
    <style type="text/css">
        body
        {
            font: normal 14px/1.5 Arial, Helvetica, sans-serif;
            color: #353535;
            outline: none;
            background: #ededed;
        }

        .box
        {
            background-color: #fff;
            margin-bottom: 25px;
            border-radius: 2px;
            position: relative;
            box-shadow: 0 1px 3px 0 rgba(0,0,0,0.2); -webkit-box-shadow: 0 1px 3px 0 rgba(0,0,0,0.2);
        }

        .box_in
        {
            padding: 4% 4%;
        }

        h3
        {
            margin: 0 0 5px 0;
        }

        a
        {
            color: #333;
        }
    </style>
</head>

<body>
<article class="box story">
    <div class="box_in" style="overflow: hidden;">

        <div class="text">
            {content}
        </div>

    </div>
</article>

</body>
</html>
