<?php
require 'includes/header.php';

$sql = "SELECT name, photo, blood_group, created_at, id_card_no 
        FROM volunteers 
        WHERE status = 'Active' 
        ORDER BY created_at ASC";
$stmt = $pdo->query($sql);
$volunteers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-[#FFF8F1]/40 min-h-screen" x-data="teamViewer">

    <div class="bg-[#FFF8F1] py-20 text-center border-b border-[#FEECDC]">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl md:text-5xl font-extrabold text-[#0F8B8D] tracking-tight">Meet Our Heroes</h1>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-[#4B5563]">
                The dedicated hearts and hands behind our mission. Together, we make change happen.
            </p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-16">

        <div class="max-w-md mx-auto mb-12 relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
            </span>
            <input type="text" x-model="search"
                class="w-full py-3 pl-10 pr-4 bg-white border border-gray-200 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-[#0F8B8D] focus:border-transparent transition"
                placeholder="Search by Name or Blood Group...">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

            <template x-for="vol in filteredVolunteers" :key="vol.id_card_no">
                <div class="bg-white rounded-[18px] shadow-md border border-gray-100 overflow-hidden hover:border-[#F4A640] hover:shadow-lg transition duration-300 group transform hover:-translate-y-1">

                    <div class="h-20 bg-gradient-to-r from-[#0F8B8D] to-[#F4A640]"></div>

                    <div class="flex justify-center -mt-10">
                        <img :src="vol.photo ? vol.photo : 'https://ui-avatars.com/api/?name=' + vol.name + '&background=fff8f1&color=0f8b8d'"
                            class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md bg-white">
                    </div>

                    <div class="p-6 text-center">
                        <h3 class="text-lg font-bold text-[#1F2937] group-hover:text-[#F4A640] transition" x-text="vol.name"></h3>
                        <p class="text-xs text-[#0F8B8D] font-semibold uppercase tracking-wider mt-1">Volunteer</p>

                        <div class="mt-4 flex justify-center gap-2 flex-wrap">

                            <template x-if="vol.blood_group">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-tint mr-1.5 text-[10px]"></i>
                                    <span x-text="vol.blood_group"></span>
                                </span>
                            </template>

                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#F0FDFD] text-[#0F8B8D] border border-[#CCFBF1]">
                                <i class="far fa-calendar-alt mr-1.5 text-[10px]"></i>
                                <span x-text="'Since ' + new Date(vol.created_at).getFullYear()"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </template>

        </div>

        <div x-show="filteredVolunteers.length === 0" class="text-center py-20 text-gray-500" x-cloak>
            <i class="far fa-sad-tear text-4xl mb-3 text-gray-300"></i>
            <p class="text-lg">No volunteers found matching your search.</p>
        </div>

    </div>

    <div class="bg-green-800 py-12 mt-12">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-2xl font-bold text-white mb-4">Want to join this amazing team?</h2>
            <a href="volunteer-register.php" class="inline-block bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-8 rounded-full shadow-lg transition transform hover:scale-105">
                Become a Volunteer
            </a>
        </div>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('teamViewer', () => ({
            search: '',
            volunteers: <?php echo json_encode($volunteers); ?>,

            get filteredVolunteers() {
                if (this.search === '') {
                    return this.volunteers;
                }
                const s = this.search.toLowerCase();
                return this.volunteers.filter(item => {
                    const name = item.name.toLowerCase();
                    const bg = item.blood_group ? item.blood_group.toLowerCase() : '';
                    return name.includes(s) || bg.includes(s);
                });
            }
        }))
    });
</script>

<?php require 'includes/footer.php'; ?>