    // --- Elective Groups Functionality ---
    
    // Toggle Electives
    $('#btn-toggle-electives').on('click', function() {
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);
        
        streamConfig.enable_elective_groups = !streamConfig.enable_elective_groups;
        renderElectiveGroups();
    });

    // Add New Group
    $('#btn-add-elective-group').on('click', function() {
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);
        
        if (!streamConfig.elective_groups) streamConfig.elective_groups = [];
        
        const newGroup = {
            id: 'g_' + Date.now(),
            semester_name: streamConfig.enable_semesters ? state.mappingSemesterId : null,
            subject_ids: [],
            total_selectable_subjects: 1
        };
        
        streamConfig.elective_groups.push(newGroup);
        renderElectiveGroups();
    });

    // Add subject to group (from dropdown)
    $('#mapping-elective-groups-container').on('change', '.add-elective-subject-select', function() {
        const groupId = $(this).data('group-id');
        const subjectId = Number($(this).val());
        if (!subjectId) return;

        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);
        
        const group = streamConfig.elective_groups.find(g => g.id === groupId);
        if (group && !group.subject_ids.includes(subjectId)) {
            group.subject_ids.push(subjectId);
        }
        renderElectiveGroups();
    });

    // Remove subject from group
    $('#mapping-elective-groups-container').on('click', '.btn-remove-subject', function() {
        const groupId = $(this).data('group-id');
        const subjectId = Number($(this).data('subject-id'));

        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);
        
        const group = streamConfig.elective_groups.find(g => g.id === groupId);
        if (group) {
            const idx = group.subject_ids.indexOf(subjectId);
            if (idx > -1) group.subject_ids.splice(idx, 1);
            
            // Re-validate total_selectable_subjects bounds
            const maxSelectable = Math.max(1, group.subject_ids.length - 1);
            if (group.total_selectable_subjects > maxSelectable) {
                group.total_selectable_subjects = maxSelectable;
            }
        }
        renderElectiveGroups();
    });

    // Change total selectable subjects
    $('#mapping-elective-groups-container').on('change', '.total-selectable-input', function() {
        const groupId = $(this).data('group-id');
        let val = parseInt($(this).val()) || 1;

        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);
        const group = streamConfig.elective_groups.find(g => g.id === groupId);
        
        if (group) {
            const maxSelectable = Math.max(1, group.subject_ids.length - 1);
            if (val > maxSelectable) val = maxSelectable;
            if (val < 1) val = 1;
            group.total_selectable_subjects = val;
            $(this).val(val);
        }
    });

    // Delete group
    $('#mapping-elective-groups-container').on('click', '.btn-delete-elective-group', function() {
        const groupId = $(this).data('group-id');
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);
        
        streamConfig.elective_groups = streamConfig.elective_groups.filter(g => g.id !== groupId);
        renderElectiveGroups();
    });

    function renderElectiveGroups() {
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);
        
        const toggleBtn = $('#btn-toggle-electives');
        const bodyBlock = $('#elective-groups-body');
        const listBlock = $('#elective-groups-list');

        if (!streamConfig.enable_elective_groups) {
            toggleBtn.text('+ ENABLE ELECTIVES').removeClass('btn-primary text-white').addClass('btn-outline-primary');
            bodyBlock.hide();
            return;
        }

        toggleBtn.text('DISABLE ELECTIVES').removeClass('btn-outline-primary').addClass('btn-primary text-white');
        bodyBlock.show();

        const currentSemester = streamConfig.enable_semesters ? state.mappingSemesterId : null;
        let groups = streamConfig.elective_groups || [];
        
        // Filter groups for current context
        const contextGroups = groups.filter(g => g.semester_name === currentSemester);

        if (!contextGroups.length) {
            listBlock.html('<div class="text-muted text-center py-4 font-italic">No elective sets configured yet. Click "+ NEW GROUP" to begin.</div>');
            return;
        }

        const allSubjects = config.masterData.subjects || [];
        
        const html = contextGroups.map((group, index) => {
            // Get already mapped subject IDs
            const mappedIds = group.subject_ids || [];
            
            // Build pills
            const pillsHtml = mappedIds.map((subId, pillIndex) => {
                const subObj = allSubjects.find(s => s.id === subId);
                const subName = subObj ? subObj.name : 'Unknown';
                const pill = `
                    <div class="elective-subject-pill">
                        ${subName}
                        <i class="mdi mdi-delete-outline btn-remove-subject" data-group-id="${group.id}" data-subject-id="${subId}"></i>
                    </div>
                `;
                return pillIndex === 0 ? pill : `<span class="elective-or-divider">OR</span> ${pill}`;
            }).join('');

            // Build Dropdown
            let optionsHtml = `<option value="">+ Add Subject</option>`;
            allSubjects.forEach(s => {
                if (state.values.subject_ids.includes(s.id) && !mappedIds.includes(s.id)) {
                    optionsHtml += `<option value="${s.id}">${s.name}</option>`;
                }
            });

            const maxSelectable = Math.max(1, mappedIds.length - 1);
            const totalSelectable = group.total_selectable_subjects || 1;

            return `
                <div class="elective-group-card">
                    <div class="elective-group-header">
                        <div class="elective-group-title">Elective Set ${index + 1}</div>
                        <i class="mdi mdi-delete-outline btn-delete-elective-group" data-group-id="${group.id}"></i>
                    </div>
                    
                    <div class="elective-group-body">
                        ${pillsHtml}
                        <div class="add-elective-subject-wrapper">
                            <select class="add-elective-subject-select" data-group-id="${group.id}">
                                ${optionsHtml}
                            </select>
                            <i class="mdi mdi-chevron-down add-elective-dropdown-icon"></i>
                        </div>
                    </div>

                    <div class="elective-group-footer">
                        <label>Total subjects student must select <i class="mdi mdi-information-outline text-muted" title="Maximum selectable is Total Subjects - 1"></i></label>
                        <input type="number" class="total-selectable-input" data-group-id="${group.id}" min="1" max="${maxSelectable}" value="${totalSelectable}">
                    </div>
                </div>
            `;
        }).join('');

        listBlock.html(html);
    }

