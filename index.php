<?php
// Set page title
$pageTitle = "Business IMS";

// Features section content
$features = [
  [
    "icon" => "fas fa-boxes",
    "title" => "Stock Management",
    "description" => "Track all your products in one place with real-time updates and low-stock alerts.",
    "link" => "#",
    "button_text" => "Learn More"
  ],
  [
    "icon" => "fas fa-chart-line",
    "title" => "Analytics",
    "description" => "View comprehensive business reports with intuitive charts and actionable insights.",
    "link" => "#",
    "button_text" => "View Details"
  ],
  [
    "icon" => "fas fa-cogs",
    "title" => "Custom Solutions",
    "description" => "Tailor the system to meet your specific business needs with flexible configuration options.",
    "link" => "#",
    "button_text" => "Explore"
  ]
];

// Stats counters
$stats = [
  ["count" => 5000, "text" => "Happy Customers"],
  ["count" => 25, "text" => "Years Experience"],
  ["count" => 12, "text" => "Industry Awards"]
];

// Testimonials
$testimonials = [
  [
    "text" => "Business IMS transformed how we manage inventory. We've reduced stockouts by 85% since implementation.",
    "author" => "Prof. Christian Jade Nalagon | University of Oxford"
  ],
  [
    "text" => "The analytics feature helped us identify our top-selling products and optimize our purchasing decisions.",
    "author" => "Prof. Milo Cagandahan | Massachusetts Institute of Technology"
  ]
];

// Social media links
$socialLinks = [
  ["icon" => "fab fa-facebook-f", "url" => "#"],
  ["icon" => "fab fa-twitter", "url" => "#"],
  ["icon" => "fab fa-linkedin-in", "url" => "#"],
  ["icon" => "fab fa-instagram", "url" => "#"]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $pageTitle; ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Google Fonts for Elegant and Strong Text -->
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">

  <!-- AOS Animation Library -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <style>
    body {
      background-color: #FFF8F1;
      font-family: 'Montserrat', sans-serif;
      overflow-x: hidden;
    }

    .navbar {
      background-color: #2E251C;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 12px 0;
      transition: all 0.3s ease;
    }

    .navbar-brand {
      color: white;
      font-weight: bold;
      padding-left: 15px;
      font-size: 1.5rem;
      letter-spacing: 1px;
    }

    .navbar-nav .nav-link {
      color: white;
      font-weight: 500;
      padding: 10px 20px;
      margin: 0 5px;
      border-radius: 20px;
      transition: all 0.3s ease;
    }

    .navbar-nav .nav-link:hover {
      background-color: #FF7F32;
      color: white;
      transform: translateY(-2px);
    }

    .hero {
      background: linear-gradient(rgba(255, 248, 241, 0.7), rgba(255, 248, 241, 0.9)), url('assets/images/index.jpg') no-repeat center center/cover;
      height: 85vh;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: #2E251C;
      text-align: center;
      padding: 0 20px;
      margin-bottom: 40px;
    }

    .hero::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 150px;
      background: linear-gradient(to top, #FFF8F1, transparent);
      z-index: 1;
    }

    .hero-content {
      z-index: 2;
      max-width: 800px;
    }

    .hero h1 {
      font-size: 4rem;
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      margin-bottom: 20px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .hero p {
      font-family: 'Merriweather', serif;
      font-size: 1.4rem;
      font-weight: 400;
      margin-top: 15px;
      margin-bottom: 30px;
      color: #2E251C;
    }

    .section-title {
      text-align: center;
      margin-top: 40px;
      margin-bottom: 40px;
      font-size: 2.5rem;
      font-weight: bold;
      color: #2E251C;
      position: relative;
      padding-bottom: 15px;
    }

    .section-title::after {
      content: '';
      position: absolute;
      width: 80px;
      height: 4px;
      background-color: #FF914D;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      border-radius: 2px;
    }

    .card {
      border: none;
      border-radius: 15px;
      padding: 30px 20px;
      text-align: center;
      background-color: #6C4F3D;
      color: white;
      transition: all 0.4s ease;
      height: 100%;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      position: relative;
    }

    .card::before {
      content: '';
      position: absolute;
      top: -10px;
      left: -10px;
      right: -10px;
      height: 10px;
      background-color: #FF914D;
      transform: translateY(-100%);
      transition: transform 0.3s ease;
    }

    .card:hover {
      transform: translateY(-10px) scale(1.02);
      background-color: #FF914D;
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .card:hover::before {
      transform: translateY(0);
    }

    .card i {
      font-size: 3rem;
      margin-bottom: 20px;
      color: #FFB347;
    }

    .card h5 {
      font-size: 1.5rem;
      margin-bottom: 15px;
      font-weight: 700;
    }

    .btn-custom {
      background-color: #FF914D;
      color: white;
      border-radius: 30px;
      padding: 12px 30px;
      text-decoration: none;
      border: none;
      font-weight: 500;
      letter-spacing: 1px;
      box-shadow: 0 4px 15px rgba(255, 145, 77, 0.3);
      transition: all 0.3s ease;
    }

    .btn-custom:hover {
      background-color: #FF7F32;
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(255, 145, 77, 0.4);
    }

    .about-section {
      background-color: #FFB347;
      padding: 60px 30px;
      border-radius: 20px;
      color: #2E251C;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      position: relative;
      overflow: hidden;
    }

    .about-section::before {
      content: '';
      position: absolute;
      width: 200px;
      height: 200px;
      background-color: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      top: -100px;
      right: -100px;
    }

    .about-text {
      font-size: 1.2rem;
      line-height: 1.8;
      max-width: 800px;
      margin: 0 auto;
    }

    footer {
      background-color: #2E251C;
      color: white;
      text-align: center;
      padding: 30px 0;
      margin-top: 80px;
      font-size: 1rem;
      position: relative;
    }

    .footer-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 20px;
    }

    .social-icons {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }

    .social-icons a {
      color: white;
      font-size: 1.5rem;
      transition: all 0.3s ease;
    }

    .social-icons a:hover {
      color: #FF914D;
      transform: translateY(-3px);
    }

    .features-section {
      background-color: #F9E1C2;
      padding: 80px 0;
      position: relative;
      overflow: hidden;
    }

    .features-section::before {
      content: '';
      position: absolute;
      width: 300px;
      height: 300px;
      background-color: rgba(255, 145, 77, 0.1);
      border-radius: 50%;
      bottom: -150px;
      left: -150px;
    }

    .counter-box {
      text-align: center;
      padding: 20px;
      margin-top: 40px;
    }

    .counter-number {
      font-size: 3rem;
      font-weight: 700;
      color: #FF914D;
      margin-bottom: 10px;
    }

    .counter-text {
      font-size: 1.2rem;
      color: #2E251C;
      font-weight: 500;
    }

    /* Testimonial section */
    .testimonial {
      background-color: white;
      border-radius: 15px;
      padding: 30px;
      margin: 20px 0;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
      position: relative;
    }

    .testimonial-text {
      font-style: italic;
      margin-bottom: 20px;
      color: #2E251C;
    }

    .testimonial-author {
      font-weight: 700;
      color: #FF914D;
    }

    .testimonial::before {
      content: '"';
      position: absolute;
      top: 10px;
      left: 20px;
      font-size: 4rem;
      color: rgba(255, 145, 77, 0.2);
      font-family: serif;
    }

    /* Animation classes */
    .fade-in {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .fade-in.visible {
      opacity: 1;
      transform: translateY(0);
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
      .hero h1 {
        font-size: 3rem;
      }
      
      .hero p {
        font-size: 1.2rem;
      }
    }

    @media (max-width: 768px) {
      .hero {
        height: 70vh;
      }

      .hero h1 {
        font-size: 2.5rem;
      }

      .section-title {
        font-size: 2rem;
      }
    }
  </style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="#"><?php echo $pageTitle; ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="#"><i class="fas fa-home me-1"></i> Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#features"><i class="fas fa-star me-1"></i> Features</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#testimonials"><i class="fas fa-comment me-1"></i> Testimonials</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#about"><i class="fas fa-info-circle me-1"></i> About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#contact"><i class="fas fa-envelope me-1"></i> Contact</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero">
  <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
    <h1>Welcome to <?php echo $pageTitle; ?></h1>
    <p>Track. Manage. Grow your business inventory easily.</p>
    <a href="auth/login.php" class="btn btn-custom btn-lg mt-3"><i class="fas fa-rocket me-2"></i>Get Started</a>
  </div>
</section>

<!-- Features Section -->
<section class="features-section" id="features">
  <div class="container">
    <h2 class="section-title" data-aos="fade-up">Our Features</h2>
    <div class="row g-4">
      <?php foreach($features as $index => $feature): ?>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo ($index+1)*100; ?>">
        <div class="card">
          <i class="<?php echo $feature['icon']; ?>"></i>
          <h5><?php echo $feature['title']; ?></h5>
          <p><?php echo $feature['description']; ?></p>
          <a href="<?php echo $feature['link']; ?>" class="btn btn-custom mt-3"><i class="fas fa-arrow-right me-1"></i> <?php echo $feature['button_text']; ?></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Stats Counters -->
    <div class="row mt-5">
      <?php foreach($stats as $index => $stat): ?>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo ($index+1)*100; ?>">
        <div class="counter-box">
          <div class="counter-number" data-count="<?php echo $stat['count']; ?>">0</div>
          <div class="counter-text"><?php echo $stat['text']; ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Testimonials Section -->
<section class="container my-5" id="testimonials">
  <h2 class="section-title" data-aos="fade-up">What Our Clients Say</h2>
  <div class="row">
    <?php foreach($testimonials as $index => $testimonial): ?>
    <div class="col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($index+1)*100; ?>">
      <div class="testimonial">
        <p class="testimonial-text">"<?php echo $testimonial['text']; ?>"</p>
        <p class="testimonial-author">- <?php echo $testimonial['author']; ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- About Section -->
<section class="container my-5" id="about">
  <div class="about-section" data-aos="fade-up">
    <h2 class="section-title">About Us</h2>
    <p class="about-text">Business IMS is the new generation of Business Inventory Management Systems, powered by AI to help businesses manage their inventory more effectively than ever. Our platform combines simple tools with real-time analytics and intelligent automation to streamline operations and reduce errors. Designed to scale with your business, it offers flexible solutions for companies of all sizes. Backed by years of industry experience, we understand the challenges businesses face in inventory management and have crafted our system to directly address those pain points with smart, adaptive technology.</p>
    <div class="text-center mt-4">
      <a href="#" class="btn btn-custom">Learn Our Story</a>
    </div>
  </div>
</section>

<!-- Contact CTA -->
<section class="container text-center my-5" data-aos="fade-up">
  <h2 class="section-title">Ready to Optimize Your Inventory?</h2>
  <p class="mb-4">Get in touch with our team to schedule a free demo and see how Business IMS can help your business.</p>
  <a href="#contact" class="btn btn-custom btn-lg">Contact Us Today</a>
</section>

<!-- Footer -->
<footer id="contact">
  <div class="container">
    <div class="footer-content">
      <div class="social-icons">
        <?php foreach($socialLinks as $link): ?>
        <a href="<?php echo $link['url']; ?>"><i class="<?php echo $link['icon']; ?>"></i></a>
        <?php endforeach; ?>
      </div>
      <p>© <?php echo date('Y'); ?> <?php echo $pageTitle; ?> | Student Project</p>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- AOS Animation JS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<!-- Custom JS -->
<script>
  // Initialize AOS animation
  AOS.init();

  // Counter animation
  document.addEventListener("DOMContentLoaded", function() {
    const counters = document.querySelectorAll('.counter-number');
    const speed = 200;

    counters.forEach(counter => {
      const animate = () => {
        const value = +counter.getAttribute('data-count');
        const data = +counter.innerText;
        
        const time = value / speed;
        if (data < value) {
          counter.innerText = Math.ceil(data + time);
          setTimeout(animate, 1);
        } else {
          counter.innerText = value;
        }
      }
      
      animate();
    });
  });

  // Navbar scroll effect
  window.addEventListener('scroll', function() {
    if (window.scrollY > 50) {
      document.querySelector('.navbar').style.padding = '8px 0';
      document.querySelector('.navbar').style.backgroundColor = 'rgba(46, 37, 28, 0.95)';
    } else {
      document.querySelector('.navbar').style.padding = '12px 0';
      document.querySelector('.navbar').style.backgroundColor = '#2E251C';
    }
  });

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      
      document.querySelector(this.getAttribute('href')).scrollIntoView({
        behavior: 'smooth'
      });
    });
  });
</script>

</body>
</html> 