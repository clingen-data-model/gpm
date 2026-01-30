<?php

namespace App\Console\Commands;

use App\Modules\Person\Actions\ProfileUpdate;
use Illuminate\Console\Command;
use App\Modules\Person\Models\Person;

class ApplyReasonableCredentialOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixup:apply-reasonable-credential-order {person_id? : Optional person ID to process, otherwise process all people}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply reasonable credential order, created in preparation for sort_order field.';

    private ProfileUpdate $profileUpdate;

    // you can blame bpow for this array... I know it's fairly hacky, but the intent is to run this once...
    private static $reasonable_but_somewhat_arbitrary_rank_order = [
        'CGC' => 40,
        'MD' => 32,
        'MSc' => 20,
        'PhD' => 35,
        'BA' => 10,
        'BSc' => 10,
        'MPH' => 20,
        'FACMG' => 41,
        'PharmD' => 30,
        'VMD' => 30,
        'MBBCh' => 31,
        'None' => 90,
        'MHS' => 20,
        'MS' => 20,
        'ClinSci' => 40,
        'ScM' => 20,
        'MLT' => 40,
        'MS, CGC' => 80,
        'MQC' => 20,
        'BM' => 29,
        'FRCP' => 40,
        'DM' => 32,
        'BCh' => 29,
        'MD, PhD, DESAIC' => 80,
        'MBA' => 20,
        'FRCPath' => 40,
        'FASN' => 40,
        'M.Eng' => 20,
        'MBBS, MRCPCH, FRCPCH' => 80,
        'MD, DM, FRCP' => 80,
        'FRCPc' => 40,
        'MD, PhD' => 80,
        'CCMG' => 40,
        'MBBS, MS, FACMG' => 80,
        'MA' => 20,
        'D (ABMLI)' => 41,
        'JD' => 33,
        'MBBS FRACP' => 80,
        'PharmD PhD' => 80,
        'MBBS' => 30,
        'BMedSc' => 20,
        'FRCPA' => 40,
        'BS' => 10,
        'FMedSci' => 40,
        'DO' => 31,
        'MPhil' => 20,
        'MB ChB' => 31,
        'DLM(ASCP)' => 41,
        'HCLD(ABB)' => 41,
        'MB(ASCP)' => 41,
        'MFD' => 32,
        'MBBS FRACP PhD' => 80,
        'MB BS FRACP PhD' => 80,
        'CLG' => 41,
        'MHM' => 20,
        'FEBLM' => 40,
        'MHGSA' => 41,
        'ErCLG' => 41,
        'DDS, PhD' => 80,
        'BEng' => 10,
        'MB BAO BCh' => 30,
        'MB BAO BCh MRCPI CHIA' => 80,
        'FCCMG' => 41,
        'MMSc' => 20,
        'DMA' => 50,
        'BASc' => 10,
        'AA' => 5,
        'AS' => 5,
        'MRCPCH' => 40,
        'MS, PhD' => 80,
        'MB BChir' => 31,
        'LLB' => 10,
        'BCL' => 20,
        'habil.' => 35,
        'FAAP' => 40,
        'MD, PhD, DABMGG' => 80,
        'FAASLD' => 40,
        'Prof.' => 80,
        'MSN' => 20,
        'medical doctor' => 31,
        'PD Dr. Dipl.-Biol.' => 31,
        'B.Tech' => 10,
        'MD, FRCP' => 80,
        'Clinical Laboratory Geneticist (ErCLG)' => 41,
        'FRS' => 41,
        'MGC' => 20,
        'DipRCPath' => 40,
        'BM BCh' => 31,
        'FRACP' => 41,
        'MEng' => 20,
        'FRANZCO' => 41,
        'FRCOphth' => 41,
        'MD(Res)' => 31,
        'FARVO' => 41,
        'MS, PhD, CGC' => 80,
        'MB(ASCP)CM' => 41,
        'MD, MBA, FARVO' => 80,
        'MBBS, MRCP, Ph.D.' => 80,
        'FRCPCH' => 41,
        'PhD, FACMG' => 80,
        'BTech' => 10,
        'ALM' => 20,
        'MChem' => 20,
        'Dipl.-Biol.' => 31,
        'dabmgg' => 50,
        'FAMH' => 41,
        'Clinical Scientist (HCPC)' => 42,
        'MSci' => 20,
        'MML (Masters in Medical Law and Ethics at the University of Edinburgh) LLB (University of Glasgow)' => 20,
        'M.B., Ch.B.' => 80,
        'MD, MS' => 80,
        'DVM' => 30,
        'Student Intern' => 80,
        'FHGSA' => 41,
        'MTech' => 20,
        'MSME' => 20,
        'FFSc' => 42,
        'CPH' => 42,
        'CGMB, CCG' => 80,
        'DClinSci' => 30,
        'MHSc' => 20,
        'PhD FACMG, FCCMG' => 80,
        'BMBS' => 30,
        'MBChB' => 20,
        'DPhil' => 32,
        'MBBS MSc PhD FRACP' => 80,
        'MBBS (Hons), FRACP, FRCPA' => 80,
        'Student Volunteer' => 80,
        'DSc' => 32,
        'MA (cantab)' => 20,
        'MClinRes' => 20,
        'MBBChir' => 20,
        'MRCP' => 40,
        'MS, GC' => 80,
        'MTOM' => 30,
        'Biologist Geneticist' => 80,
        'Master of Engineering' => 80,
        'Biochemist' => 80,
        'MLIS' => 20,
        'MGCS' => 20,
        'BOptom' => 10,
        'PharmaD' => 30,
        'MA, MS, CGC' => 80,
        'MD MsC' => 80,
        'ABMGG LGG trainee/fellow' => 80,
        'PSM' => 20,
        'FHKAM(Paedi)' => 41,
        'MSc, PhD' => 80,
        'MBBS, MSc, PhD, FACMG, FADLM' => 80,
        'MS, LCGC' => 80,
        'FFSc (RCPA)' => 41,
        'FAHMS' => 41,
        'MBBS FRACP MSc BScH' => 80,
        'Prof, PhD Biochemist' => 80,
        'MBBS, MD, DM' => 80,
        'PhD, SMB (ASCP)' => 80,
        'ScM, CGC' => 80,
        'MD, PhD, professor of medicine' => 80,
        'PhD, HCLD, CGC' => 80,
        'CG(ASCP)MB' => 41,
        'MS, LGC' => 80,
        'MD PhD' => 80,
        'CMLS' => 41,
        'LGCG' => 42,
        'MBBS FRCP FRCOphth PhD' => 80,
        'BMLS' => 10,
        'PgDip BioMed Sci' => 31,
        'FRCSC' => 41,
        'PhD, FCCMG' => 80,
        'BSc (Hons)' => 21,
        'Specializing in Medical Genetics' => 80,
        'MBBS, DNB (O&G), DrNB (Medical Genetics)' => 80,
        'MD, MSc' => 80,
        'MD, MPH' => 80,
        'MSc. Genetic Counseling' => 20,
        'MPSA' => 20,
        'MBBS (Hons) PhD FRACP FRCPA' => 80,
        'PMP' => 41,
        'DASM' => 42,
        'PhD, MB(ASCP)' => 42,
        'MSHGG' => 20,
        'MSc, CGC' => 80,
        'BMSc' => 10,
        'MS, MS, LGC' => 80,
        'LLB(Hons)' => 11,
        'BBiomedSc(Hons)' => 11,
        'MBBS, MD, Dip.RCPath' => 80,
        'BBiomedSc' => 10,
        'MS, MLS(ASCP), MB(ASCP)' => 80,
        'BMBCh' => 31,
        'Bioinformatics' => 80,
        'M.Tech' => 20,
        'MBBS,MD,DrNB Medical Genetics' => 80,
        'PhD, HCLD (ABB)' => 80,
        'CSC-E' => 42,
        'PhD, HCLD(ABB)' => 80,
        'BMBCh MA MPH MRCP FRCOphth DM' => 80,
        'MRes' => 20,
        'Master of Genome Analytics' => 20,
        'MD, FAAP, FACMG' => 80,
        'CCS' => 42,
        'ARCPA' => 42,
        'FCCP' => 42,
        'BCPS' => 42,
        'MD, MPH, MA' => 80,
        'MS MPH LCGC' => 80,
        'PhD(c)' => 29,
        'Medical Genetic Resident' => 80,
        'PhD, PDF, CGC' => 80,
        'Dr. rer.nat.' => 32,
        'Clinical Biochemist Specialized in Genetics' => 80,
        'PhD, MHGSA' => 80,
    ];


    /**
     * Execute the console command.
     */
    public function handle(ProfileUpdate $profileUpdate): int
    {
        $changeCount = 0;
        $this->profileUpdate = $profileUpdate;
        $personId = $this->argument('person_id');

        if ($personId) {
            $person = Person::find($personId);

            if (!$person) {
                $this->error("Person with ID {$personId} not found.");
                return 1;
            }

            $people = collect([$person]);
            $numPeople = 1;
        } else {
            $people = Person::has('credentials');
            $numPeople = $people->count();
            $people = $people->cursor();
        }
        $numPeople = $people->count();
        $progressBar = $this->output->createProgressBar($numPeople);
        $this->info("Processing {$numPeople} people with credentials...");
        $progressBar->start();
        $people->each(function ($person) use (&$changeCount, $progressBar) {
            if ($this->applyCredentialOrder($person)) {
                $changeCount++;
            }
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->newLine();
        $this->info("Done! {$changeCount} of {$numPeople} people had their credential order updated.");

        return 0;
    }

    private function applyCredentialOrder(Person $person): bool
    {
        $credentials = $person->credentials;
        $origIds = $credentials->pluck('id')->toArray();

        if ($credentials->count() <= 1) {
            return false;
        }
        $credentials = $credentials->sortBy(function ($credential) {
            return self::$reasonable_but_somewhat_arbitrary_rank_order[$credential->name] ?? 99;
        });

        $newIds = $credentials->pluck('id')->toArray();
        if ($origIds === $newIds) {
            return false;
        } else {
            $this->info("Updating credential order for person ID {$person->id} ({$person->full_name})");
            $this->profileUpdate->handle($person, ['credential_ids' => $credentials->pluck('id')->toArray()]);
            return true;
        }
    }
}
