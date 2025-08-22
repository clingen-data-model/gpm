<script>
import api from '@/http/api';
import SearchSelect from '@/components/forms/SearchSelect.vue';

export default {
    name: 'CuratedGeneSearchSelect',
    components: { SearchSelect },
    props: {
        modelValue: { type: [Object, null], default: null }
    },
    emits: ['update:modelValue'],
    computed: {
        selectedOption: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit('update:modelValue', value);
            }
        }
    },
    methods: {
        async search(searchText) {
			const otherOption = {
				curation_id: 'other',
				hgnc_id: '',
				gene_symbol: 'Other (Not curated)',
				disease_name: '',
				mondo_id: '',
				moi: '',
				moi_name: '',
				hp_id: '',
				expert_panel: '',				
				date_approved: '',
				classification: '',
				curation_status: '',
				phenotypes: '',
				phenotypeIDs: '',
				checkKey: '',
				is_other: true
			};

            if (! searchText || searchText.length < 3) {
				return [otherOption];
			}

            const response = await api.get(`/api/curations`, { params: { query: searchText } })
										.then(response => response.data);
			if(response.data.length === 0) {
				return [otherOption];
			}

			const curatedResults = response.data
				.filter(item => {
					const classificationId = item.classification_id;
					const statusId = item.curation_status_id;
					return [1, 2, 3, 4].includes(classificationId) && statusId === 9;
				})
				.map(item => {					
					return {
						is_other: false,
						...item 
					};
				});

            curatedResults.push(otherOption);

			return curatedResults;
        }
    },
};
</script>

<template>
	<SearchSelect
		v-model="selectedOption"
		:search-function="search"
		placeholder="Search curated Gene-MonDO ID-MoI"
		style="z-index: 21"
		key-options-by="id"
		optionsHeight="400"
	>
		<!-- How selected value looks -->
		<template #selection-label="{selection}">			
			<div v-if="selection?.is_other">
				Other (Not curated)
			</div>
			<div v-else-if="selection">
				{{ selection.gene_symbol }} - {{ selection.mondo_id }} - {{ selection.moi }}
			</div>
			<div v-else>
			</div>
		</template>

		<!-- How dropdown options look -->
		<template #option="{option}">
			<div v-if="option?.is_other">
				<strong>Other (Not curated)</strong>
			</div>
			<div v-else>
				<div class="font-semibold" >
					{{ option.gene_symbol }} | {{ option.mondo_id }} | MoI: {{ option.moi }} 
					<span v-if="['Moderate','Limited'].includes(option.classification)" class="font-bold text-orange-500">⚠</span>
				</div>
				<div class="grid grid-cols-2 gap-x-4 text-xs text-gray-600 leading-snug">
					<div><strong>Disease:</strong> {{ option.disease_name }}</div>
					<div><strong>Expert Panel:</strong> {{ option.expert_panel }}</div>
					<div>
						<strong>Classification:</strong> {{ option.classification ?? 'N/A' }}
						<span v-if="['Moderate','Limited'].includes(option.classification)" class="font-bold text-orange-500">⚠</span>
					</div>
					<div><strong>Status:</strong> {{ option.curation_status ?? 'N/A' }}</div>
					<div><strong>Date Approved:</strong> 
						{{ new Date(option.date_approved).toLocaleDateString('en-US', {
							month: 'short', // Dec
							day: '2-digit', // 14
							year: 'numeric', // 2025
							hour: '2-digit', // 02
							minute: '2-digit', // 30
							hour12: true     // AM/PM
						}) }}</div>
					<div class="col-span-2"><strong>Phenotypes:</strong> {{ option.phenotypes }}</div>
				</div>
			</div>
		</template>
	</SearchSelect>
</template>
