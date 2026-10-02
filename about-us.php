<?php
require 'includes/header.php';
?>

<style>

.about-content ul{
    list-style:disc;
    padding-left:25px;
    margin:15px 0;
}

.about-content ol{
    list-style:decimal;
    padding-left:25px;
    margin:15px 0;
}

.about-content li{
    margin-bottom:8px;
}

.about-content strong,
.about-content b{
    font-weight:700;
    color:#0F8B8D;
}

.about-content em,
.about-content i{
    font-style:italic;
}

</style>

<?php

$data = [];

$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'about_%'");

while ($row = $stmt->fetch()) {
    $data[$row['setting_key']] = $row['setting_value'];
}

?>

<div class="bg-white text-[#1F2937]">

    <div style="padding-bottom: 1.5rem !important;" class="bg-[#FFF8F1] py-16 text-center border-b border-[#FEECDC]">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-[#0F8B8D] tracking-tight">
                <?php echo htmlspecialchars($data['about_title'] ?? 'About Our Journey'); ?>
            </h1>
            <p class="mt-4 max-w-3xl mx-auto text-lg text-[#4B5563]">Serving Humanity. Building a Better Tomorrow.</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-16 md:py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div class="relative group order-1 lg:order-2">
                <div class="absolute -inset-2 bg-gradient-to-r from-[#F0FDFD] to-[#FFF8F1] rounded-[18px] blur-lg opacity-50"></div>
                <div class="relative">
                    <img src="<?php echo !empty($data['about_image']) ? $data['about_image'] : 'https://placehold.co/800x600/e0e7ff/3730a3?text=Our+Team'; ?>"
                        alt="About Us Image"
                        class="rounded-[18px] shadow-2xl w-full object-contain max-h-[500px] border-4 border-white">
                </div>
            </div>

            <div class="order-2 lg:order-1 space-y-6">
                <p class="w-16 h-16 bg-[#F0FDFD] text-[#0F8B8D] rounded-full flex items-center justify-center mb-6"><i class="fas fa-people-group text-2xl"></i></p>
               <div class="text-lg text-[#4B5563] leading-relaxed space-y-4 text-justify about-content">
    <?php
    echo !empty($data['about_desc'])
        ? nl2br($data['about_desc'])
        : 'Content will be updated soon.';
    ?>
</div>
                <div class="pt-4">
                    <a href="donate.php" class="inline-block bg-[#F4A640] text-white font-bold py-3 px-8 rounded-full shadow-lg hover:bg-[#D98E2B] transition transform hover:-translate-y-1">
                        Get Involved
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-[#FFF8F1] py-16 md:py-8 border-t border-b border-[#FEECDC]">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">

                <div class="bg-white p-8 rounded-[18px] shadow-lg border border-gray-100 hover:shadow-2xl hover:border-[#F4A640] transition-all duration-300 transform hover:-translate-y-2">
                    <div class="w-16 h-16 bg-[#F0FDFD] text-[#0F8B8D] rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-rocket text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-[#1F2937] mb-4">Our Mission</h3>
<div class="text-[#4B5563] leading-relaxed about-content">
    <?php
    echo !empty($data['about_mission'])
        ? nl2br($data['about_mission'])
        : 'To serve humanity by providing essential resources and opportunities for growth.';
    ?>
</div>
                </div>

                <div class="bg-white p-8 rounded-[18px] shadow-lg border border-gray-100 hover:shadow-2xl hover:border-[#F4A640] transition-all duration-300 transform hover:-translate-y-2">
                    <div class="w-16 h-16 bg-[#FFF8F1] text-[#F4A640] rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-eye text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-[#1F2937] mb-4">Our Vision</h3>
<div class="text-[#4B5563] leading-relaxed about-content">
    <?php
    echo !empty($data['about_vision'])
        ? nl2br($data['about_vision'])
        : 'A world where every individual has the opportunity to thrive and live with dignity.';
    ?>
</div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-[#0F8B8D]">
        <div class="container mx-auto px-4 py-16 text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                Ready to Make a Difference?
            </h2>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-white/90">
                Your support can turn our vision into reality. Join us in our mission to create a better world.
            </p>

            <div class="mt-8 flex flex-row items-center justify-center gap-3">

                <a href="donate.php"
                    class="inline-flex items-center justify-center bg-[#F4A640] hover:bg-[#D98E2B] text-white font-bold py-3 px-5 md:px-8 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-sm md:text-base w-auto">
                   
                    <span>Donate Now</span>
                </a>

                <a href="volunteer-register.php"
                    class="inline-flex items-center justify-center bg-white hover:bg-gray-100 text-[#0F8B8D] font-bold py-3 px-5 md:px-8 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-sm md:text-base w-auto whitespace-nowrap">
                    <span>Join as Volunteer</span>
                </a>

            </div>
        </div>
    </div>

</div>

<?php require 'includes/footer.php'; ?>
