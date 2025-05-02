<?php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('location:login.php');
    exit;
}

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    echo "Product ID missing.";
    exit;
}

// Log product view
mysqli_query($conn, "INSERT INTO product_views(user_id, product_id) VALUES('$user_id', '$product_id')") or die('View log failed');

// Fetch product details
$product_query = mysqli_query($conn, "SELECT * FROM products WHERE id = '$product_id'") or die('query failed');
if (mysqli_num_rows($product_query) == 0) {
    echo "Product not found.";
    exit;
}
$product = mysqli_fetch_assoc($product_query);

$name = $product['name'];
$price = $product['price'];
$image = $product['image'];
$genre = $product['genre'];
$author = $product['author'];
$tags = explode(',', $product['tags']);

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_quantity = $_POST['product_quantity'];

    $check_cart = mysqli_query($conn, "SELECT * FROM cart WHERE name = '$name' AND user_id = '$user_id'");
    if (mysqli_num_rows($check_cart) > 0) {
        $message[] = 'Already added to cart!';
    } else {
        mysqli_query($conn, "INSERT INTO cart(user_id, name, price, quantity, image) VALUES('$user_id', '$name', '$price', '$product_quantity', '$image')") or die('Add to cart failed');
        $message[] = 'Product added to cart!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $name; ?> - Product Details</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
    .product-details {
        display: flex;
        gap: 30px;
        justify-content: center;
        padding: 20px 10px; /* Reduced */
    }

    .product-details img {
        width: 300px;
        height: auto;
        object-fit: cover;
        border-radius: 8px;
    }

    .product-info {
        max-width: 500px;
    }

    .product-info h2 {
        font-size: 2rem;
        margin-bottom: 6px; /* Reduced */
    }

    .product-info .price {
        color: #28a745;
        font-size: 1.5rem;
        margin: 6px 0; /* Reduced */
    }

    .product-info .btn {
        margin-top: 10px; /* Slightly reduced */
        padding: 10px 20px;
        background-color: #007bff;
        border: none;
        color: white;
        border-radius: 5px;
        cursor: pointer;
    }

    .product-info .btn:hover {
        background-color: #0056b3;
    }

    .recommendations {
        padding: 20px 10px; /* Reduced */
        background-color: #f9f9f9;
    }

    .recommendations h2 {
        text-align: center;
        font-size: 2rem;
        margin-bottom: 20px; /* Reduced */
    }

    .box-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
    }

    .box {
        background-color: #fff;
        width: 250px;
        border-radius: 8px;
        overflow: hidden;
        text-align: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .box img {
        width: 100%;
        height: auto;
    }

    .box h3 {
        font-size: 1.1rem;
        padding: 6px 0; /* Reduced */
    }

    .box p {
        color: #28a745;
        margin: 5px 0;
    }

    .box .btn {
        margin-bottom: 12px; /* Slightly reduced */
        padding: 8px 16px;
        background-color: #007bff;
        border: none;
        color: white;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
    }

    .box .btn:hover {
        background-color: #0056b3;
    }
</style>

</head>
<body>

<?php include 'header.php'; ?>

<div class="heading">
    <h3>Book Details</h3>
    <p><a href="home.php">Home</a> / View Product</p>
</div>

<<section class="product-details">
    <div class="product-card">
        <div class="product-image">
            <img src="uploaded_img/<?php echo $image; ?>" alt="<?php echo $name; ?>">
        </div>
        <div class="product-info">
            <h2><?php echo $name; ?></h2>
            <p class="price">Rs <?php echo $price; ?> /-</p>
            <p><strong>Author:</strong> <?php echo $author; ?></p>
            <p><strong>Genre:</strong> <?php echo $genre; ?></p>
            <p><strong>Tags:</strong> <?php echo implode(', ', $tags); ?></p>
            
            <div class="action">
                <input type="number" min="1" value="1" class="qty">
                <button class="btn">Add to Cart</button>
            </div>
        </div>
    </div>
</section>

<section class="recommendations">
    <h2>Recommended Books</h2>
    <div class="box-container">
        <?php
        $recommend_query = mysqli_query($conn, "SELECT * FROM products WHERE id != '$product_id'");
        $recommended = [];

        while ($row = mysqli_fetch_assoc($recommend_query)) {
            $score = 0;
            if ($row['genre'] === $genre) $score += 3;
            if ($row['author'] === $author) $score += 2;

            $row_tags = explode(',', $row['tags']);
            $common_tags = array_intersect($tags, $row_tags);
            $score += count($common_tags);

            if ($score > 0) {
                $row['score'] = $score;
                $recommended[] = $row;
            }
        }

        usort($recommended, fn($a, $b) => $b['score'] - $a['score']);

        if (count($recommended) === 0) {
            echo "<p>No similar books found.</p>";
        } else {
            foreach ($recommended as $book) {
                ?>
                <div class="box">
                    <img src="uploaded_img/<?php echo $book['image']; ?>" alt="">
                    <h3><?php echo $book['name']; ?></h3>
                    <p>Rs<?php echo $book['price']; ?>/-</p>
                    <a href="view_product.php?id=<?php echo $book['id']; ?>" class="btn">View</a>
                </div>
                <?php
            }
        }
        ?>
    </div>
</section>

<?php include 'footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
