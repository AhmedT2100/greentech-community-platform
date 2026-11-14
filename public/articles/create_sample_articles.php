<?php
require_once __DIR__ . '/../config.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Sample articles to insert
    $sampleArticles = [
        [
            'title' => 'Welcome to GreenTech 🌱',
            'content' => 'This is our first article about sustainable living. Discover how you can make a difference in your daily life by adopting eco-friendly practices. From reducing plastic waste to using renewable energy, every small step counts towards a greener future.',
            'image' => 'demo1.jpg'
        ],
        [
            'title' => 'Eco-Friendly Innovations',
            'content' => 'Discover the latest innovations in recycling, renewable energy, and sustainable living. Learn about cutting-edge technologies that are transforming how we interact with our environment. From solar panels to wind turbines, these innovations are powering a sustainable future.',
            'image' => 'demo2.jpg'
        ],
        [
            'title' => 'Climate Change: What You Need to Know',
            'content' => 'Climate change is one of the most pressing challenges of our time. This article explores the science behind climate change, its impacts on our planet, and what we can do to help mitigate its effects. Understanding the issue is the first step towards creating a sustainable future.',
            'image' => 'demo3.jpg'
        ],
        [
            'title' => 'Sustainable Fashion: The Future of Clothing',
            'content' => 'The fashion industry is one of the largest polluters in the world. This article discusses sustainable fashion practices, ethical clothing brands, and how consumers can make environmentally conscious choices when shopping for clothes.',
            'image' => 'demo4.jpg'
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO articles (title, content, image, created_at) VALUES (?, ?, ?, NOW())");
    
    $count = 0;
    foreach ($sampleArticles as $article) {
        $stmt->execute([$article['title'], $article['content'], $article['image']]);
        $count++;
    }
    
    echo "<h2>Success!</h2>";
    echo "<p>Added $count sample articles to the database.</p>";
    echo "<p><a href='index.php'>View all articles</a></p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
