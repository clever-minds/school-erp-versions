$(function () {
    let currentDiaryPage = 1;
    let isLoaded = false;

    // Elements
    const typeSelect = document.getElementById('diary_type');
    const monthSelect = document.getElementById('diary_month');
    const cardsContainer = document.getElementById('diary_cards_container');
    const loadingIndicator = document.getElementById('diary_loading');
    const paginationContainer = document.getElementById('diary_pagination');

    if (!cardsContainer) return;

    // Event Listeners
    if (typeSelect) typeSelect.addEventListener('change', () => { currentDiaryPage = 1; loadDiaryData(); });
    if (monthSelect) monthSelect.addEventListener('change', () => { currentDiaryPage = 1; loadDiaryData(); });

    // Load data when tab is opened
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#diary' || $(e.target).attr('id') === 'diary-tab') {
            if (!isLoaded) {
                isLoaded = true;
                loadDiaryData();
            }
        }
    });

    // Check if it's active initially
    if ($('#diary').hasClass('active') || $('#diary').hasClass('show')) {
        isLoaded = true;
        loadDiaryData();
    }

    function loadDiaryData() {
        const type = typeSelect ? typeSelect.value : '';
        let month = '';
        let year = '';

        if (monthSelect && monthSelect.selectedIndex > 0) {
            const opt = monthSelect.options[monthSelect.selectedIndex];
            month = opt.value;
            year = opt.getAttribute('data-year');
        }

        const studentId = window.DIARY_STUDENT_ID || '';
        const sessionYearId = window.DIARY_SESSION_YEAR_ID || '';
        const fetchUrl = window.DIARY_FETCH_URL || '';

        // Show loading
        cardsContainer.innerHTML = '';
        cardsContainer.style.display = 'none';
        loadingIndicator.style.display = 'block';
        paginationContainer.innerHTML = '';

        const url = `${fetchUrl}?student_id=${studentId}&session_year_id=${sessionYearId}&page=${currentDiaryPage}&type=${type}&month=${month}&year=${year}`;

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Network response error');
                return response.json();
            })
            .then(data => {
                loadingIndicator.style.display = 'none';
                cardsContainer.style.display = 'block';

                // Update Badges
                if (data.summary) {
                    const totalEl = document.getElementById('diary_total_count');
                    const posEl = document.getElementById('diary_positive_count');
                    const negEl = document.getElementById('diary_negative_count');
                    if (totalEl) totalEl.textContent = data.summary.total || 0;
                    if (posEl) posEl.textContent = data.summary.positive || 0;
                    if (negEl) negEl.textContent = data.summary.negative || 0;
                }

                renderDiaryCards(data.diaries);
            })
            .catch(error => {
                console.error('Error fetching diaries:', error);
                loadingIndicator.style.display = 'none';
                cardsContainer.style.display = 'block';
                cardsContainer.innerHTML = `
                    <div class="text-center py-5">
                        <span class="text-danger"><i class="fa fa-exclamation-triangle fa-2x mb-3"></i><br>Failed to load diary data</span>
                    </div>
                `;
            });
    }

    function renderDiaryCards(pagedData) {
        cardsContainer.innerHTML = '';

        if (!pagedData || !pagedData.data || pagedData.data.length === 0) {
            cardsContainer.innerHTML = `
                <div class="text-center py-5">
                    <div class="text-muted">
                        <i class="fa fa-folder-open fa-3x mb-3" style="color: #cbd5e1;"></i>
                        <h5 class="text-secondary">No diary entries found.</h5>
                        <p class="mb-0">Please adjust filters or check later.</p>
                    </div>
                </div>
            `;
            return;
        }

        // Create cards sequentially
        pagedData.data.forEach(entry => {

            // Determine badge class and border class
            let borderClass = 'border-left-primary';
            let badgeClass = 'badge-soft-primary';
            let typeIcon = 'fa-info-circle';

            if (entry.type === 'positive') {
                borderClass = 'border-left-success';
                badgeClass = 'badge-soft-success';
                typeIcon = 'fa-check-circle';
            } else if (entry.type === 'negative') {
                borderClass = 'border-left-danger';
                badgeClass = 'badge-soft-danger';
                typeIcon = 'fa-exclamation-triangle';
            }


            const descriptionContent = entry.description ? entry.description.replace(/</g, "&lt;").replace(/>/g, "&gt;") : '<i class="text-light-gray">No description provided</i>';
            const typeCapitalized = entry.type.charAt(0).toUpperCase() + entry.type.slice(1);

            console.log(entry);


            const subjectHtml = entry.subject_label ? `<span class="badge badge-pill bg-light border ml-auto">${entry.subject_label}</span>` : '';

            // Card HTML
            const cardHtml = `
                <div class="card diary-card shadow-sm ${borderClass}" style="transition: transform 0.2s ease-in-out;">
                    <div class="card-body p-3 p-md-4">
                        <div class="row align-items-md-start">
                            <!-- Left Status Section -->
                            <div class="col-12 col-md-3 col-lg-2 diary-card-left mb-3 mb-md-0 d-flex flex-row flex-md-column justify-content-between justify-content-md-start align-items-center align-items-md-start">
                                <span class="badge ${badgeClass} px-3 py-2 rounded-lg d-inline-block">
                                    <i class="fa ${typeIcon} mr-1"></i> ${typeCapitalized}
                                </span>
                                <span class="diary-card-date mt-md-3">
                                    <i class="fa fa-clock mr-1"></i> ${entry.date}
                                </span>
                            </div>
                            
                            <!-- Right Content Section -->
                            <div class="col-12 col-md-9 col-lg-10">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="diary-card-title mb-0">${entry.title}</h5>
                                    ${subjectHtml}
                                </div>
                                <p class="diary-card-desc mb-4">${descriptionContent}</p>
                                
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border-top pt-3">
                                    <span class="diary-card-meta mb-2 mb-sm-0 text-uppercase">
                                        CATEGORY: ${entry.category_name}
                                    </span>
                                    <span class="diary-card-author text-muted d-flex align-items-center">
                                        <i class="fa fa-user-o mr-2"></i> Added by: <span class="text-dark font-weight-bold ml-1">${entry.created_by}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const cardEl = document.createElement('div');
            cardEl.innerHTML = cardHtml;
            const innerCard = cardEl.firstElementChild;

            // Add minor hover effect
            innerCard.addEventListener('mouseenter', function () {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05)';
            });
            innerCard.addEventListener('mouseleave', function () {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 .125rem .25rem rgba(0,0,0,.075)';
            });

            cardsContainer.appendChild(innerCard);
        });

        renderPagination(pagedData);
    }

    function renderPagination(pagedData) {
        paginationContainer.innerHTML = '';

        if (pagedData.last_page <= 1) return;

        const nav = document.createElement('nav');
        const ul = document.createElement('ul');
        ul.className = 'pagination pagination-sm mb-0';

        // Prev Button
        const prevLi = document.createElement('li');
        prevLi.className = 'page-item ' + (pagedData.current_page === 1 ? 'disabled' : '');
        prevLi.innerHTML = `<a class="page-link" href="javascript:void(0)" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>`;
        if (pagedData.current_page > 1) {
            prevLi.addEventListener('click', () => { currentDiaryPage = pagedData.current_page - 1; loadDiaryData(); });
        }
        ul.appendChild(prevLi);

        // Page Numbers
        let startPage = Math.max(1, pagedData.current_page - 2);
        let endPage = Math.min(pagedData.last_page, pagedData.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = 'page-item ' + (i === pagedData.current_page ? 'active' : '');
            pageLi.innerHTML = `<a class="page-link" href="javascript:void(0)">${i}</a>`;
            if (i !== pagedData.current_page) {
                pageLi.addEventListener('click', () => { currentDiaryPage = i; loadDiaryData(); });
            }
            ul.appendChild(pageLi);
        }

        // Next Button
        const nextLi = document.createElement('li');
        nextLi.className = 'page-item ' + (pagedData.current_page === pagedData.last_page ? 'disabled' : '');
        nextLi.innerHTML = `<a class="page-link" href="javascript:void(0)" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>`;
        if (pagedData.current_page < pagedData.last_page) {
            nextLi.addEventListener('click', () => { currentDiaryPage = pagedData.current_page + 1; loadDiaryData(); });
        }
        ul.appendChild(nextLi);

        nav.appendChild(ul);
        paginationContainer.appendChild(nav);
    }
});
