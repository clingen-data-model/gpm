<template>
  <div class="centered-container">
    <h1>ClinGen Demographic Survey</h1>
    <!-- Background and Purpose section -->
    <section>
      <h2>Background and Purpose</h2>
      <p>
        <b>Why are we asking for this information?</b> The Clinical Genome Resource values the diversity of our
        participants and works to maintain a culture of acceptance, accessibility, and diversity (see our Justice, Equity,
        Diversity, and Inclusion (JEDI) Action Plan):
        <a href="https://clinicalgenome.org/working-groups/jedi-advisory-board/" target="_blank">Visit site</a> Providing
        this information will help us to better understand our ClinGen community, focus current efforts to enhance
        diversity, and identify areas for future outreach. We sincerely appreciate your time, attention, and willingness
        to help.
      </p>
      <p><b>Who can access my data?</b> Only a small number of ClinGen staff members will have access to the demographic
        data. We will not share any individual data. Data may be shared/presented in aggregate.</p>

      <p><b>What do we do with this data?</b>This is entirely for internal and informational use to understand our
        participants. For example, we might use this information to focus efforts on engaging early or mid-career
        scientists, or to develop materials for enhanced learning accessibility based on participant feedback.</p>

      <p> Please note: Select 'Prefer not to answer' for any questions you do not wish to answer</p>

      <!-- Additional paragraphs and sections -->
    </section>
    <!-- Participant Information section -->
    <section>
      <h2>Participant Information on Country</h2>

      <p>The current list of countries comes from the international standard ISO 3166 country codes (<a
          href="https://www.iso.org/iso-3166-country-codes.html" target="_blank">Visit site</a>). We recognize that this
        list may not be complete or satisfy all, so please feel free to choose “other” and provide a free text response.
      </p>
      <div>

      </div>
      <!-- Country of Birth Dropdown -->
      <div>
        <label for="birth_country">Country of Birth: </label>
        <select id="birth_country" name="birth_country" v-model="birth_country">
          <option value="">Select country</option>
          <option v-for="country in countries" :key="country.value" :value="country.value">{{ country.label }}</option>
        </select>
      </div>

      <div style="display: flex;">
        <label>Other: </label>
        <input class="w3-input" type="text">
      </div>

      <div style="display: flex;">
        <input type="checkbox" class="checkbox-margin" id="optOutBornCountry" v-model="optOutBornCountry">
        <label for="optOutBornCountry"> Prefer not to answer</label>

      </div>
      <br>
      <div>
        <!-- Country of Residence Dropdown -->
        <label for="reside_country">Country of Residence: </label>
        <select id="reside_country" name="reside_country" v-model="reside_country">

          <option value="">Select country</option>
          <option v-for="country in countries" :key="country.value" :value="country.value">{{ country.label }}</option>
        </select>
      </div>

      <div style="display: flex;">

        <label>Other: </label>
        <input class="w3-input" type="text">
      </div>
      <div style="display: flex;">
        <input type="checkbox" id="optOutResideCountry" v-model="optOutResideCountry">
        <label for="optOutResideCountry">Prefer not to answer </label>

      </div>
      <br>

      <!-- Additional inputs and sections -->
      <br>
      <div>
        <label for="country-state">If you currently live in the United States, what is your state/territory of
          residence?</label>

        <!--- United States states -->

        <select id="country-state" name="country-state" v-model="country_state">
          <option value="">Select state</option>
          <option value="AL">Alabama</option>
          <option value="AK">Alaska</option>
          <option value="AS">American Samoa</option>
          <option value="AZ">Arizona</option>
          <option value="AR">Arkansas</option>
        </select>
      </div>
      <div style="display: flex;">
        <input id="opt_out" class="w3-check" type="checkbox" v-model="optOutState">
        <label>Prefer not to answer </label>

      </div>
      <br>
      <h2>Participant Information on Race/Ethnicity</h2>

      <p>ClinGen Participant Diversity: We ask the following question for aggregate informational purposes to understand
        ClinGen participant diversity. The free-text response can be used in place or in addition to the listed
        categories. </p>

      <p> The options below come from updates by the U.S. 2020 Census (<a href="https://www.census.gov/"
          target="_blank">Visit site</a>), and the All Of Us Basic Survey Questions (<a
          href="https://www.researchallofus.org/data-tools/survey-explorer/the-basics-survey/" target="_blank">Visit
          site</a>). We understand that these options do not capture the diversity of racial and ethnic identities in
        the US, let alone around the world. </p>

      <legend>Which categories describe you? Select all that apply. Note, you may select more than one group.</legend>
      <div style="display: flex;">
        <input type="checkbox" id="ethnicity1" name="ethnicity1" value="American Indian" class="container"
          v-model="ethnicity1_val">
        <label for="ethnicity1">American Indian or Alaska Native (For example: Aztec, Blackfeet Tribe, Mayan, Navajo
          Nation, Native Village of Barrow (Utqiagvik) Inupiat Traditional Government, Nome Eskimo Community,
          etc.)</label><br>
      </div>

      <div style="display: flex;">
        <input type="checkbox" id="ethnicity2" name="ethnicity2" value="Asian" class="container" v-model="ethnicity2_val">
        <label for="ethnicity2">Asian (For example: Asian Indian, Chinese, Filipino, Japanese, Korean, Vietnamese,
          etc.)</label><br>
      </div>

      <div style="display: flex;">
        <input type="checkbox" id="ethnicity3" name="ethnicity3" value="Black" class="container" v-model="ethnicity3_val">
        <label for="ethnicity3">Black, African American, or African (For example: African American, Ethiopian, Haitian,
          Jamaican, Nigerian, Somali, etc.)</label><br>
      </div>
      <div style="display: flex;">
        <input type="checkbox" id="ethnicity4" name="ethnicity4" value="Hispanic" class="container"
          v-model="ethnicity4_val">
        <label for="ethnicity4">Hispanic, Latino, or Spanish (For example: Colombian, Cuban, Dominican, Mexican or
          Mexican American, Puerto Rican, Salvadoran, etc.)</label><br>
      </div>
      <div style="display: flex;">
        <input type="checkbox" id="ethnicity5" name="ethnicity5" value="Middle Eastern" class="container"
          v-model="ethnicity5_val">
        <label for="ethnicity5">Middle Eastern or North African (For example: Algerian, Egyptian, Iranian, Lebanese,
          Moroccan, Syrian, etc.)</label><br>
      </div>
      <div style="display: flex;">
        <input type="checkbox" id="ethnicity6" name="ethnicity6" value="Pacific" class="container"
          v-model="ethnicity6_val">
        <label for="ethnicity6">Native Hawaiian or other Pacific Islander (For example: Chamorro, Fijian, Marshallese,
          Native Hawaiian, Tongan, etc.)</label><br>
      </div>
      <div style="display: flex;">
        <input type="checkbox" id="ethnicity7" name="ethnicity7" value="White" class="container" v-model="ethnicity7_val">
        <label for="ethnicity7">White (For example: English, European, French, German, Irish, Italian, Polish,
          etc.)</label>
      </div>

      <div style="display: flex;">
        <input id="optOutEthnicity" class="w3-check" type="checkbox"><br>
        <label>Prefer not to answer</label>

      </div>
      <br>
      <h2>Participant Information on Age</h2>
      <br>
      <div style="display: flex; align-items: center;">
        <label>What year were you born?</label>
        <input class="w3-input" type="text" id="birth_year" v-model="birth_year" required>
      </div>


      <div style="display: flex;">
        <input id="optOutBirth" class="w3-check" type="checkbox">
        <label> Prefer not to answer</label>

      </div>

      <br>
      <h2>Participant Information on Identity</h2>

      <p>We ask the following questions about identity to understand participation across the ClinGen ecosystem. The
        framing of the questions around sex and gender reflects recommendations made by the National Academies of
        Sciences, Engineering, and Medicine in 2022. (<a href="https://nap.nationalacademies.org/read/26424/chapter/1#xi"
          target="_blank">Visit site</a>) </p>

      <div class="w3-section">
        <legend>Which categories describe you? Select all that apply. Note, you may select more than one group.</legend>
        <label>
          <input type="checkbox" name="identity1" v-model="identity1" value="Female">Female<br>

        </label>
        <label>
          <input type="checkbox" name="identity2" value="Male">Male<br>
        </label>
        <label>
          <input type="checkbox" name="identify3" value="Unsure">Unsure<br>
        </label>
        <label>
          <input type="checkbox" name="identity4" value="None">Prefer not to answer<br>
        </label>
      </div>

      <div style="display: flex;">
        <label>Other: </label>
        <input class="w3-input" type="text">
      </div>


      <br>
      <br>
      <div class="w3-section">
        <legend>What term best describes your gender identity? Select all that apply.</legend>
        <label>
          <input type="checkbox" name="gender" value="Man">Man<br>
        </label>
        <label>
          <input type="checkbox" name="gender" value="Woman">Woman<br>
        </label>
        <label>
          <input type="checkbox" name="gender" value="Cis">Cisgender<br>
        </label>


        <label>
          <input type="checkbox" name="gender" value="Nonbinary">Nonbinary<br>
        </label>
        <label>
          <input type="checkbox" name="gender" value="Transgender">Transgender<br>
        </label>
        <label>
          <input type="checkbox" name="gender" value="Genderqueer">Genderqueer<br>
        </label>
        <label>
          <input type="checkbox" name="gender" value="Agender">Agender<br>
        </label>
        <label>
          <input type="checkbox" name="gender" value="Intersex">Intersex<br>
        </label>

        <label>
          <input type="checkbox" name="gender" value="Unsure">Unsure<br>
        </label>
        <label>
          <input type="checkbox" name="gender" value="None">Prefer not to answer<br>
        </label>
      </div>

      <div style="display: flex;">
        <label>My preferred term is </label>
        <input class="w3-input" type="text">
      </div>


      <br>
      <h2>ClinGen Background and Experience</h2>

      <br>
      <div class="w3-section">
        <legend>How is your ClinGen work supported? Select all that apply.</legend>
        <label>
          <input type="checkbox" name="support" value="volunteer">Volunteer outside of work environment<br>
        </label>
        <label>
          <input type="checkbox" name="support" value="grant">Grants (e.g. NIH, foundational)
        </label>
        <div style="display: flex;">
          <label>Provide more details on source of funding</label>
          <input class="w3-input" type="text">

        </div>

        <br>
        <label>
          <input type="checkbox" name="support" value="employer">Employer supports/allows participation<br>
        </label>
        <label>
          <input type="checkbox" name="support" value="Unsure">Unsure<br>
        </label>
        <label>
          <input type="checkbox" name="support" value="None">Prefer not to answer<br>
        </label>

        <div style="display: flex;">
          <label>Other: </label>
          <input class="w3-input" type="text">
        </div>



      </div>
      <br>

      <h2>Under-Represented Groups and Disadvantaged Backgrounds</h2>

      <p>ClinGen is invested in expanding access to curated data and participation in Expert Panels/working groups to
        individuals who may be under-represented or experience disadvantage due to location or life events. We ask these
        questions to help understand the backgrounds of our participating members.</p>

      <p>The following text pertains to the disadvantaged background question below. If you are not a US-based
        participant, please answer the question based on similar criteria in your own country:</p>
      <br>
      <p>An individual is considered to be from a disadvantaged background if meeting two or more of the following
        criteria:<br>
        Were or currently are homeless, as defined by the McKinney-Vento Homeless Assistance Act (<a
          href="https://nche.ed.gov/mckinney-vento/" target="_blank">Definition</a>);<br>
        Were or currently are in the foster care system, as defined by the Administration for Children and Families (<a
          href="https://www.acf.hhs.gov/cb/focus-areas/foster-care" target="_blank">Definition</a>);<br>
        Were eligible for the Federal Free and Reduced Lunch Program for two or more years (<a
          href="https://www.fns.usda.gov/school-meals/income-eligibility-guidelines" target="_blank">Definition</a>);
        <br>
        Have/had no parents or legal guardians who completed a bachelor’s degree (<a
          href="https://nces.ed.gov/pubs2018/2018009.pdf" target="_blank">See</a>);<br>
        Were or currently are eligible for Federal Pell grants (<a
          href="https://www2.ed.gov/programs/fpg/eligibility.html" target="_blank">Definition</a>);<br>
        Received support from the Special Supplemental Nutrition Program for Women, Infants and Children (WIC) as a
        parent or child (<a href="https://www.fns.usda.gov/wic/wic-eligibility-requirements"
          target="_blank">Definition</a>);<br>
        Grew up in one of the following areas: <br>
        a) a U.S. rural area, as designated by the Health Resources and Services Administration (HRSA) Rural Health
        Grants Eligibility Analyzer (<a href="https://data.hrsa.gov/tools/rural-health" target="_blank">Definition</a>);
        or<br>
        b) a Centers for Medicare and Medicaid Services-designated Low-Income and Health Professional Shortage Areas
        (qualifying zip codes are included in the file). (<a
          href="https://www.qhpcertification.cms.gov/s/LowIncomeandHPSAZipCodeListingPY2020.xlsx?v=1" target="_blank">View
          File</a>) Only one of these two possibilities can be used as a criterion for the
        disadvantaged background definition.

      </p>


      Based on the NIH definition above, do you consider yourself currently in or having come from a disadvantaged
      background? Note: If you are not a US-based participant, please answer based on similar criteria in your own
      country.<br>
      <label>
        <input type="radio" name="disadvantaged" id="yes" value="yes">Yes<br>
      </label>
      <label>
        <input type="radio" name="disadvantaged" id="no" value="no">No<br>
      </label>
      <label>
        <input type="radio" name="disadvantaged" id="unsure" value="Unsure">Unsure<br>
      </label>
      <label>
        <input type="radio" name="disadvantaged" id="none" value="None">Prefer not to answer<br>
      </label>

      <div style="display: flex;">
        <label>Optional: Use this free text box to provide any additional detail.</label>
        <input id="optOutDisadvantaged" class="w3-input" type="text">
      </div>





      <br>
      <h2>Employment</h2>



      Please choose the option that most accurately describes your role or occupation [select all that apply].<br>
      <div style="display: flex; align-items: center; margin-bottom: 5px;">
        <input type="checkbox" id="genetics-physician" name="occupation" value="genetics physician">
        <label for="genetics-physician">Medical genetics physician</label>
      </div>

      <div style="display: flex; align-items: center; margin-bottom: 5px;">
        <input type="checkbox" id="non genetics-physician" name="occupation" value="non genetics physician">
        <label for="non genetics-physician">Medical non-genetics physician</label>
      </div>


      <div style="display: flex; align-items: center; margin-bottom: 5px;">
        <input type="checkbox" id="pathologist" name="occupation" value="pathologist" class="container checkbox-margin">
        <label for="pathologist">Molecular pathologist</label>
      </div>

      <label>
        <input type="checkbox" name="occupation" value="clinical geneticist">Clinical laboratory geneticist<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Genetic counselor">Genetic counselor<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="resident">Clinical resident or fellow <br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Basic researcher">Basic researcher<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Clinical researcher">Clinical researcher<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Variant Analyst">Variant Analyst<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Staff Scientist">Staff Scientist<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Bioinformatician">Bioinformatician<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Biocurator">Biocurator<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Graduate Student">Graduate Student<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Software Engineer/Developer">Software Engineer/Developer<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Educator">Educator<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="General Geneticist">General Geneticist<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="Science Policy">Health Care Policy or Science Policy<br>
      </label>
      <label>
        <input type="checkbox" name="occupation" value="None">Prefer not to answer<br>
      </label>

      <div style="display: flex;">
        <label>Other: Use this free text box to provide any additional detail. </label>
        <input id="optOutOccupation" class="w3-input" type="text">
      </div>



      <br>


      <label for="specialty">If you indicated “Medical non-genetics physician”, please select your
        specialty.</label><span style="color: red !important; display: inline; float: none;"></span>
      <select id="specialty" name="specialty"><br>
        <option value="">Select specialty</option>

        <option value="Allergy & Immunology">Allergy & Immunology</option>
        <option value="Anesthesiology">Anesthesiology</option>
        <option value="Cardiology/Cardiovascular Disease">Cardiology/Cardiovascular Disease</option>
        <option value="Child and Adolescent Psychiatry">Child and Adolescent Psychiatry</option>
        <option value="Colon & Rectal Surgery">Colon & Rectal Surgery</option>
        <option value="Critical Care Medicine">Critical Care Medicine</option>

        <option value="Cytopathology">Cytopathology</option>
        <option value="Dermatology">Dermatology</option>
        <option value="Emergency Medicine">Emergency Medicine</option>
        <option value="Endocrinology, Diabetes and Metabolism">Endocrinology, Diabetes and Metabolism</option>
        <option value="Family Medicine">Family Medicine</option>
        <option value="Gastroenterology">Gastroenterology</option>

        <option value="General Preventive Medicine and Public Health">General Preventive Medicine and Public Health
        </option>
        <option value="Geriatric Medicine">Geriatric Medicine</option>
        <option value="Hematology">Hematology</option>
        <option value="Hospice and Palliative Medicine">Hospice and Palliative Medicine</option>
        <option value="Infectious Diseases">Infectious Diseases</option>
        <option value="Internal Medicine">Internal Medicine</option>

        <option value="Interventional Cardiology">Interventional Cardiology</option>
        <option value="Medical Genetics and Genomics">Medical Genetics and Genomics</option>
        <option value="Nephrology">Nephrology</option>
        <option value="Ophthalmology">Ophthalmology</option>
        <option value="Neurological Surgery">Neurological Surgery</option>
        <option value="Neurology">Neurology</option>

        <option value="Nuclear Medicine">Nuclear Medicine</option>
        <option value="Obstetrics & Gynecology">Obstetrics & Gynecology</option>
        <option value="Occupational Medicine">Occupational Medicine</option>
        <option value="Oncology">Oncology</option>
        <option value="Orthopaedic Sports Medicine">Orthopaedic Sports Medicine</option>
        <option value="Orthopaedic Surgery">Orthopaedic Surgery</option>

        <option value="Otolaryngology">Otolaryngology</option>
        <option value="Pain Medicine">Pain Medicine</option>
        <option value="Pathology">Pathology</option>
        <option value="Pediatric Surgery">Pediatric Surgery</option>
        <option value="Pediatrics">Pediatrics</option>
        <option value="Physical Medicine & Rehabilitation">Physical Medicine & Rehabilitation</option>

        <option value="Plastic Surgery">Plastic Surgery</option>
        <option value="Preventive Medicine">Preventive Medicine</option>
        <option value="Psychiatry">Psychiatry</option>
        <option value="Pulmonary Disease and Critical Care Medicine">Pulmonary Disease and Critical Care Medicine
        </option>
        <option value="Radiation Oncology">Radiation Oncology</option>
        <option value="Radiology">Radiology</option>

        <option value="Rheumatology">Rheumatology</option>
        <option value="Sleep Medicine">Sleep Medicine</option>
        <option value="Surgery - General">Surgery - General</option>
        <option value="Thoracic Surgery">Thoracic Surgery</option>
        <option value="Urology">Urology</option>
        <option value="Vascular Surgery">Vascular Surgery</option>
      </select>
      <br>



      <br>


      <!-- Submission Button -->
      <button @click="addSurvey">Submit Demographic Survey</button>


    </section>
    <div v-if="saving" class="mb-2">Saving...</div>
    <button-row v-if="!saving" @submitted="save" @canceled="cancel()" :submit-text="saveButtonText"
      :show-cancel="allowCancel" />
  </div>
</template>
  
<script setup>
import { ref } from 'vue'
import { useStore } from 'vuex'
import Person from '@/domain/person'

const store = useStore()
const props = defineProps({
  person: {
    type: Object,
    required: true
  },
})

const errors = ref({})
const demographicsProfile = ref({})
const saving = ref(false)

const initDemographicsProfile = () => {
  demographicsProfile.value = { ...props.person.demographics }
}

const save = async () => {
  try {
    saving.value = true
    const updatedPerson = await store.dispatch(
      'people/updateDemographics',
      { uuid: props.person.uuid, attributes: demographicsProfile.value }
    ).then(rsp => {
      store.dispatch('getCurrentUser', { force: true })
      store.commit('pushSuccess', 'Your demographics profile has been updated.')
      return rsp.data;
    })

    saving.value = false;
    errors.value = {};
    emits('saved', new Person(updatedPerson));
  } catch (error) {
    if (isValidationError(error)) {
      errors.value = error.response.data.errors;
    }
  }
}

const cancel = () => {
  initDemographicsProfile();
  errors.value = {};
  emits('canceled');
};

const countries = computed(() => {
  return store.getters['countries/items'].map(i => ({ value: i.id, label: i.name }))
})

onMounted(() => {
  store.dispatch('countries/getItems');
});

</script>
