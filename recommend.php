<?php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('location:login.php');
    exit();
}

$no_recommendation = false;

// 1. Get the last viewed product
$last_view_query = mysqli_query($conn, "
    SELECT products.* FROM product_views 
    JOIN products ON product_views.product_id = products.id 
    WHERE product_views.user_id = '$user_id'
    ORDER BY product_views.viewed_at DESC LIMIT 1
") or die('Query failed');

if (mysqli_num_rows($last_view_query) == 0) {
    $no_recommendation = true;
} else {
    $last_viewed = mysqli_fetch_assoc($last_view_query);
    $genre = $last_viewed['genre'];
    $author = $last_viewed['author'];
    $tags = explode(',', $last_viewed['tags']);

    // 2. Fetch other products and score them
    $recommend_query = mysqli_query($conn, "
        SELECT * FROM products 
        WHERE id != '{$last_viewed['id']}'
    ") or die('Query failed');

    $recommended = [];

    while ($row = mysqli_fetch_assoc($recommend_query)) {
        $score = 0;
        if ($row['genre'] == $genre) $score += 3;
        if ($row['author'] == $author) $score += 2;

        $row_tags = explode(',', $row['tags']);
        $score += count(array_intersect($tags, $row_tags));

        if ($score > 0) {
            $row['score'] = $score;
            $recommended[] = $row;
        }
    }

    usort($recommended, fn($a, $b) => $b['score'] - $a['score']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Recommended Books</title>
   <link rel="stylesheet" href="css/style.css">
   <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }

    .recommendations {
        background-color: #f9f9f9;
        padding: 40px 20px;
        margin-top: 40px;
    }

    .recommendations h1.title {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
        text-align: center;
        margin-bottom: 20px;
    }

    .box-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        gap: 20px;
    }

    .box {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        width: 300px;
        text-align: center;
        transition: transform 0.3s ease;
    }

    .box:hover {
        transform: translateY(-10px);
    }

    .box img {
        width: 100%;
        height: auto;
        object-fit: cover;
        border-bottom: 2px solid #eee;
    }

    .box h3 {
        font-size: 1.2rem;
        font-weight: bold;
        margin: 10px 0;
        color: #333;
    }

    .box p {
        font-size: 1rem;
        color: #28a745;
        margin-bottom: 15px;
    }

    .box .btn {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .box .btn:hover {
        background-color: #0056b3;
    }

    .message {
        text-align: center;
        font-size: 1.2rem;
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
        padding: 20px;
        margin: 40px auto;
        width: 60%;
        border-radius: 10px;
    }
   </style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="recommendations">
   <h1 class="title">Recommended For You</h1>

   <?php if ($no_recommendation): ?>
      <div class="message">No recommendations available. Please view a product first to get suggestions.</div>
   <?php else: ?>
      <div class="box-container">
         <?php if (count($recommended) > 0): ?>
            <?php foreach ($recommended as $rec): ?>
               <div class="box">
                  <img src="uploaded_img/<?php echo $rec['image']; ?>" alt="">
                  <h3><?php echo $rec['name']; ?></h3>
                  <p>Rs<?php echo $rec['price']; ?></p>
                  <a href="view_product.php?id=<?php echo $rec['id']; ?>" class="btn">View</a>
               </div>
            <?php endforeach; ?>
         <?php else: ?>
            <div class="message">No similar products found.</div>
         <?php endif; ?>
      </div>
   <?php endif; ?>
</section>

<?php include 'footer.php'; ?>

</body>
</html>
