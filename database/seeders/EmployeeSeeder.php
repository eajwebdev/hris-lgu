<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Bootstrap personnel. The Mayor, Vice Mayor and HR head are required because
 * the leave workflow routes through them (see SettingSeeder).
 *
 * Every account below uses the password: password123
 * Change these immediately after the first sign-in.
 *
 * NOTE: Employee::boot() hashes the password on `creating`, so a password
 * passed through the model would be hashed twice on insert (and left in plain
 * text on update). The hash is therefore written straight to the table below,
 * which behaves the same whether the row is new or existing.
 */
class EmployeeSeeder extends Seeder
{
    public function run()
    {

        $people = [
            [
                'emp_ID' => '2026-0001',
                'fname' => 'Juan',
                'mname' => 'Santos',
                'lname' => 'Dela Cruz',
                'position' => 'Municipal Mayor',
                'emp_dept' => 3,            // Office of the Mayor
                'username' => 'mayor@mabinay.gov.ph',
                'org_email' => 'mayor@mabinay.gov.ph',
                'supervisor' => null,
            ],
            [
                'emp_ID' => '2026-0002',
                'fname' => 'Maria',
                'mname' => 'Reyes',
                'lname' => 'Bautista',
                'position' => 'Municipal Vice Mayor',
                'emp_dept' => 4,            // Office of the Vice Mayor
                'username' => 'vicemayor@mabinay.gov.ph',
                'org_email' => 'vicemayor@mabinay.gov.ph',
                'supervisor' => null,
            ],
            [
                'emp_ID' => '2026-0003',
                'fname' => 'Ana',
                'mname' => 'Lim',
                'lname' => 'Villanueva',
                'position' => 'Human Resource Management Officer',
                'emp_dept' => 6,            // HRMO
                'username' => 'hr@mabinay.gov.ph',
                'org_email' => 'hr@mabinay.gov.ph',
                'supervisor' => null,
            ],
            [
                'emp_ID' => '2026-0004',
                'fname' => 'Pedro',
                'mname' => 'Cruz',
                'lname' => 'Ramos',
                'position' => 'Administrative Aide IV',
                'emp_dept' => 6,            // HRMO
                'username' => 'employee@mabinay.gov.ph',
                'org_email' => 'employee@mabinay.gov.ph',
                'supervisor' => null,         // set below, once HR head has an id
            ],
            [
                'emp_ID' => '2026-0005',
                'fname' => 'Anabel',
                'mname' => 'G.',
                'lname' => 'Estolonio',
                'position' => 'Utility Personnel (COS)',
                'emp_status' => 'Job Order',
                'emp_dept' => 19,           // General Services Office (GSO)
                'username' => 'jo_anabel',
                'org_email' => 'jo_anabel@mabinay.gov.ph',
                'supervisor' => null,
            ],
            [
                'emp_ID' => '2026-0006',
                'fname' => 'Carlos',
                'mname' => 'M.',
                'lname' => 'Santos',
                'position' => 'Administrative Aide (JO)',
                'emp_status' => 'Job Order',
                'emp_dept' => 6,            // HRMO
                'username' => 'jo_carlos',
                'org_email' => 'jo_carlos@mabinay.gov.ph',
                'supervisor' => null,
            ],
        ];

        // Regular employees imported from "HRIS EMPLOYEES - REGULAR .pdf" (public/),
        // filtered to rows that have both an Employee ID Number and an Email Address —
        // the PDF has no department/position data, so those columns are left null.
        // NOTE: the source PDF lists duplicate Employee ID Numbers for two pairs
        // (1091-05 and 8711-04, flagged below). Since emp_ID is the lookup key for
        // updateOrCreate, the second row in each pair overwrites the first.
        $regularEmployees = [
            ['emp_ID' => '8711-153', 'fname' => 'Clyde', 'mname' => 'Cataytay', 'lname' => 'Abendan', 'username' => 'claudineabendan@gmail.com', 'org_email' => 'claudineabendan@gmail.com'],
            ['emp_ID' => '7611-01', 'fname' => 'Melba', 'mname' => 'Ramos', 'lname' => 'Abril', 'username' => 'melbar.abril@yahoo.com', 'org_email' => 'melbar.abril@yahoo.com'],
            ['emp_ID' => '1021-14', 'fname' => 'Marjorie', 'mname' => 'Jamito', 'lname' => 'Abrio', 'username' => 'marjorieabrio12@gmail.com', 'org_email' => 'marjorieabrio12@gmail.com'],
            ['emp_ID' => '8811-06', 'fname' => 'Ednalyn', 'mname' => 'Banong', 'lname' => 'Academia', 'username' => 'ednalynacademia9@gmail.com', 'org_email' => 'ednalynacademia9@gmail.com'],
            ['emp_ID' => '1071-01', 'fname' => 'Mary Ann', 'mname' => 'Yuzon', 'lname' => 'Acaso', 'username' => 'bebeth_acaso@yahoo.com', 'org_email' => 'bebeth_acaso@yahoo.com'],
            ['emp_ID' => '8751-10', 'fname' => 'Isabelito', 'mname' => 'Emperado', 'lname' => 'Agustin', 'username' => 'agustinisabelito9@gmail.com', 'org_email' => 'agustinisabelito9@gmail.com'],
            ['emp_ID' => '1071-12', 'fname' => 'Andre Jun', 'mname' => 'Quiliope', 'lname' => 'Agustin', 'username' => 'andre.budgetoffice@gmail.com', 'org_email' => 'andre.budgetoffice@gmail.com'],
            ['emp_ID' => '4411-31', 'fname' => 'Meraliza', 'mname' => 'Tejones', 'lname' => 'Alas-As', 'username' => 'jemeraltejo@gmail.com', 'org_email' => 'jemeraltejo@gmail.com'],
            ['emp_ID' => '1081-06', 'fname' => 'Karen Lou', 'mname' => 'Mogar', 'lname' => 'Alindajao', 'username' => 'karenloualindajao28@gmail.com', 'org_email' => 'karenloualindajao28@gmail.com'],
            ['emp_ID' => '1061-03', 'fname' => 'Chaild Mae', 'mname' => null, 'lname' => 'Amado', 'username' => 'chaildmaeamado@gmail.com', 'org_email' => 'chaildmaeamado@gmail.com'],
            ['emp_ID' => '7611-03', 'fname' => 'Perla', 'mname' => 'Resentes', 'lname' => 'Amante', 'username' => 'jenzyjau20@gmail.com', 'org_email' => 'jenzyjau20@gmail.com'],
            ['emp_ID' => '1012-01B', 'fname' => 'Dindo', 'mname' => 'Mana-Ay', 'lname' => 'Amorganda', 'username' => 'adhinndz@yahoo.com', 'org_email' => 'adhinndz@yahoo.com'],
            ['emp_ID' => '1041-02', 'fname' => 'Brian', 'mname' => 'Durango', 'lname' => 'Ausejo', 'username' => 'bausejo@yahoo.com', 'org_email' => 'bausejo@yahoo.com'],
            ['emp_ID' => '1091-13', 'fname' => 'Emelisa', 'mname' => 'Palma', 'lname' => 'Balderas', 'username' => 'emelisabalderas1973@gmail.com', 'org_email' => 'emelisabalderas1973@gmail.com'],
            ['emp_ID' => '1101-04', 'fname' => 'Mishelle', 'mname' => 'Baldado', 'lname' => 'Baldoza', 'username' => 'baldozamishelle@gmail.com', 'org_email' => 'baldozamishelle@gmail.com'],
            ['emp_ID' => '8711-08', 'fname' => 'Pablito', 'mname' => 'Gando', 'lname' => 'Baldoza', 'username' => 'pablitobaldoza@gmail.com', 'org_email' => 'pablitobaldoza@gmail.com'],
            ['emp_ID' => '1091-09', 'fname' => 'Liniedo Jr.', 'mname' => 'Grapa', 'lname' => 'Banong', 'username' => 'lgbanong@ymail.com', 'org_email' => 'lgbanong@ymail.com'],
            // Duplicate emp_ID 1091-05 with Torres, Caroline Mae below — second row wins.
            ['emp_ID' => '1091-05', 'fname' => 'Marlyn', 'mname' => 'Cagas', 'lname' => 'Barrera', 'username' => 'marlynbarrera1973@gmail.com', 'org_email' => 'marlynbarrera1973@gmail.com'],
            ['emp_ID' => '1011-272', 'fname' => 'Nolriz', 'mname' => 'Caliso', 'lname' => 'Bitanghol', 'username' => 'nolrizbong@gmail.com', 'org_email' => 'nolrizbong@gmail.com'],
            ['emp_ID' => '1071-10', 'fname' => 'Joseth', 'mname' => 'Pampilo', 'lname' => 'Buscato', 'username' => 'budgetoffice.joseth@gmail.com', 'org_email' => 'budgetoffice.joseth@gmail.com'],
            ['emp_ID' => '1012-08C15', 'fname' => 'Michael', 'mname' => 'Emfat', 'lname' => 'Cabugnason', 'username' => 'mikecabz80@gmail.com', 'org_email' => 'mikecabz80@gmail.com'],
            ['emp_ID' => '1041-04', 'fname' => 'Rolly', 'mname' => 'Caandi', 'lname' => 'Cadalzo', 'username' => 'rcadalzo30@gmail.com', 'org_email' => 'rcadalzo30@gmail.com'],
            ['emp_ID' => '4411-16', 'fname' => 'Melanie', 'mname' => 'Sala', 'lname' => 'Cadalzo', 'username' => 'melaniesalacadalzo@gmail.com', 'org_email' => 'melaniesalacadalzo@gmail.com'],
            ['emp_ID' => '1012-02C1', 'fname' => 'Vince Francis', 'mname' => 'Nocos', 'lname' => 'Cadayday', 'username' => 'im84623@gmail.com', 'org_email' => 'im84623@gmail.com'],
            ['emp_ID' => '1051-04', 'fname' => 'Cicero', 'mname' => 'Ondoy', 'lname' => 'Cadiz', 'username' => 'cicerocadiz345@gmail.com', 'org_email' => 'cicerocadiz345@gmail.com'],
            ['emp_ID' => '1081-02', 'fname' => 'Janice', 'mname' => 'Cadayona', 'lname' => 'Cadiz', 'username' => 'janicecadayonacadiz@gmail.com', 'org_email' => 'janicecadayonacadiz@gmail.com'],
            // Duplicate emp_ID 8711-04 with Socorro, Kevin Gil below — second row wins.
            ['emp_ID' => '8711-04', 'fname' => 'Rosemar', 'mname' => 'Lofrangco', 'lname' => 'Cadorna', 'username' => 'rose.shai13@gmail.com', 'org_email' => 'rose.shai13@gmail.com'],
            ['emp_ID' => '8811-33', 'fname' => 'Normandy', 'mname' => 'Kabristante', 'lname' => 'Carpio Jr.', 'username' => 'ydnamronkc@gmail.com', 'org_email' => 'ydnamronkc@gmail.com'],
            ['emp_ID' => '4411-07', 'fname' => 'Charlotte', 'mname' => 'Oro', 'lname' => 'Creer', 'username' => 'charlottecreer330@gmail.com', 'org_email' => 'charlottecreer330@gmail.com'],
            ['emp_ID' => '1051-06', 'fname' => 'Amie Rose', 'mname' => 'Aba', 'lname' => 'Cueco', 'username' => 'amierosecueco1980@gmail.com', 'org_email' => 'amierosecueco1980@gmail.com'],
            ['emp_ID' => '1051-02', 'fname' => 'Fritsie', 'mname' => 'Martinez', 'lname' => 'Dela Peña', 'username' => 'stirf111@gmail.com', 'org_email' => 'stirf111@gmail.com'],
            ['emp_ID' => '1021-17', 'fname' => 'Azucenas', 'mname' => 'Narciso', 'lname' => 'Durango', 'username' => 'azucenadurango73@gmail.com', 'org_email' => 'azucenadurango73@gmail.com'],
            ['emp_ID' => '4411-30', 'fname' => 'Myrla', 'mname' => 'Tumapa', 'lname' => 'Elnar', 'username' => 'myrlatumapa@gmail.com', 'org_email' => 'myrlatumapa@gmail.com'],
            ['emp_ID' => '4411-29', 'fname' => 'Nelsie', 'mname' => 'Lobo', 'lname' => 'Escander', 'username' => 'nelsiescander@gmail.com', 'org_email' => 'nelsiescander@gmail.com'],
            ['emp_ID' => '4411-38', 'fname' => 'Linalyn', 'mname' => 'Acaso', 'lname' => 'Ferrer', 'username' => 'lynnferrer85@gmail.com', 'org_email' => 'lynnferrer85@gmail.com'],
            ['emp_ID' => '1011-259', 'fname' => 'Marie Apple Lorraine', 'mname' => 'Mission', 'lname' => 'Futalan', 'username' => 'applelorrainef@gmail.com', 'org_email' => 'applelorrainef@gmail.com'],
            ['emp_ID' => '1051-01', 'fname' => 'Catalina', 'mname' => 'Ladesma', 'lname' => 'Garces', 'username' => 'zardandy64@gmail.com', 'org_email' => 'zardandy64@gmail.com'],
            ['emp_ID' => '4411-08', 'fname' => 'Gayle Mae', 'mname' => 'Demetita', 'lname' => 'Garces', 'username' => 'elyagmae@gmail.com', 'org_email' => 'elyagmae@gmail.com'],
            ['emp_ID' => '1012-04C3', 'fname' => 'Nico', 'mname' => 'Embang', 'lname' => 'Garces', 'username' => 'garcesnico143@gmail.com', 'org_email' => 'garcesnico143@gmail.com'],
            ['emp_ID' => '1101-01', 'fname' => 'Bernadeth', 'mname' => 'Temonio', 'lname' => 'Guanzon', 'username' => 'guanzonbernadeth1@gmail.com', 'org_email' => 'guanzonbernadeth1@gmail.com'],
            ['emp_ID' => '1021-16', 'fname' => 'Rodito', 'mname' => 'Teleron', 'lname' => 'Hermosada', 'username' => 'hermosadarodito@gmail.com', 'org_email' => 'hermosadarodito@gmail.com'],
            ['emp_ID' => '1012-07C6', 'fname' => 'Joefrey', 'mname' => 'Custodio', 'lname' => 'Herrera', 'username' => 'herrejean073@gmail.com', 'org_email' => 'herrejean073@gmail.com'],
            ['emp_ID' => '1061-06', 'fname' => 'Michael', 'mname' => 'Cataylo', 'lname' => 'Hongcuay', 'username' => 'miekee102976@gmail.com', 'org_email' => 'miekee102976@gmail.com'],
            ['emp_ID' => '1091-06', 'fname' => 'Joseph', 'mname' => 'Ere', 'lname' => 'Hucal', 'username' => 'josephhucal08@gmail.com', 'org_email' => 'josephhucal08@gmail.com'],
            ['emp_ID' => '7611-46', 'fname' => 'Anthony', 'mname' => 'De La Rosa', 'lname' => 'Ibero', 'username' => 'anthony.ibero@gmail.com', 'org_email' => 'anthony.ibero@gmail.com'],
            ['emp_ID' => '1061-23', 'fname' => 'Junnah Rel', 'mname' => 'Caparida', 'lname' => 'Igpit', 'username' => 'ijunnahrel@gmail.com', 'org_email' => 'ijunnahrel@gmail.com'],
            ['emp_ID' => '8711-16', 'fname' => 'Jenessa', 'mname' => 'Tongcua', 'lname' => 'Java', 'username' => 'javajenessa@gmail.com', 'org_email' => 'javajenessa@gmail.com'],
            ['emp_ID' => '1101-02', 'fname' => 'Ivy', 'mname' => 'Villapando', 'lname' => 'Kadusale', 'username' => 'ivykadusale7@gmail.com', 'org_email' => 'ivykadusale7@gmail.com'],
            ['emp_ID' => '8751-06', 'fname' => 'Jerelito', 'mname' => 'Jorolan', 'lname' => 'Lado', 'username' => 'jerelitojorolanlado@gmail.com', 'org_email' => 'jerelitojorolanlado@gmail.com'],
            ['emp_ID' => '1071-03', 'fname' => 'Janice', 'mname' => 'Gantalao', 'lname' => 'Laluna', 'username' => 'janice.budgetoffice@gmail.com', 'org_email' => 'janice.budgetoffice@gmail.com'],
            ['emp_ID' => '8711-06', 'fname' => 'Ira May', 'mname' => 'Cadalzo', 'lname' => 'Landiza', 'username' => 'iracadalzo@gmail.com', 'org_email' => 'iracadalzo@gmail.com'],
            ['emp_ID' => '1101-03', 'fname' => 'Riche', 'mname' => 'Ejercito', 'lname' => 'Lastimoso', 'username' => 'richelastimoso@gmail.com', 'org_email' => 'richelastimoso@gmail.com'],
            ['emp_ID' => '1081-03', 'fname' => 'Karen Jean', 'mname' => 'Agor', 'lname' => 'Anfone-Lobos', 'username' => 'kjanfone1118@gmail.com', 'org_email' => 'kjanfone1118@gmail.com'],
            ['emp_ID' => '8711-148', 'fname' => 'Karen Joy', 'mname' => 'Cornelio', 'lname' => 'Lovina', 'username' => 'karenjoylovina26@gmail.com', 'org_email' => 'karenjoylovina26@gmail.com'],
            ['emp_ID' => '8711-01', 'fname' => 'Lelanie', 'mname' => 'Anfone', 'lname' => 'Malacapay', 'username' => 'laniemalacapay@gmail.com', 'org_email' => 'laniemalacapay@gmail.com'],
            ['emp_ID' => '1011-193', 'fname' => 'Apolinario', 'mname' => 'Parong', 'lname' => 'Mission', 'username' => 'apolinariomission798@gmail.com', 'org_email' => 'apolinariomission798@gmail.com'],
            ['emp_ID' => '1091-07', 'fname' => 'Lolibeth', 'mname' => 'Baldoza', 'lname' => 'Narciso', 'username' => 'lolibethnarciso13@gmail.com', 'org_email' => 'lolibethnarciso13@gmail.com'],
            ['emp_ID' => '1081-04', 'fname' => 'Mila Flor', 'mname' => 'Samonte', 'lname' => 'Nares', 'username' => 'milaflornares@gmail.com', 'org_email' => 'milaflornares@gmail.com'],
            ['emp_ID' => '1061-01', 'fname' => 'Lucrecia', 'mname' => 'Carreon', 'lname' => 'Nicolas', 'username' => 'luc31570@gmail.com', 'org_email' => 'luc31570@gmail.com'],
            ['emp_ID' => '1091-04', 'fname' => 'Mary Cel', 'mname' => 'Yuson', 'lname' => 'Niñal', 'username' => 'marycelninal@gmail.com', 'org_email' => 'marycelninal@gmail.com'],
            ['emp_ID' => '4411-34', 'fname' => 'Chris Emmanuel', 'mname' => 'Jainar', 'lname' => 'Novera', 'username' => 'chrisgame8888@gmail.co', 'org_email' => 'chrisgame8888@gmail.co'],
            ['emp_ID' => '1011-02', 'fname' => 'Ma. Rosario', 'mname' => 'Ferrer', 'lname' => 'Ocay', 'username' => 'marosarioferrerocay@gmail.com', 'org_email' => 'marosarioferrerocay@gmail.com'],
            ['emp_ID' => '1081-05', 'fname' => 'Marissa', 'mname' => 'Dela Peña', 'lname' => 'Ojeda', 'username' => 'marissaojeda54@gmail.com', 'org_email' => 'marissaojeda54@gmail.com'],
            ['emp_ID' => '1061-20', 'fname' => 'Genevieve', 'mname' => 'Cañete', 'lname' => 'Omandac', 'username' => 'vieveomandac@gmail.com', 'org_email' => 'vieveomandac@gmail.com'],
            ['emp_ID' => '1011-19', 'fname' => 'James', 'mname' => 'Vergara', 'lname' => 'Ones', 'username' => 'gawix_james@yahoo.com', 'org_email' => 'gawix_james@yahoo.com'],
            ['emp_ID' => '8811-02', 'fname' => 'Elisa', 'mname' => 'Baldado', 'lname' => 'Pancho', 'username' => 'elizapancho@gmail.com', 'org_email' => 'elizapancho@gmail.com'],
            ['emp_ID' => '1021-13', 'fname' => 'Jenifer', 'mname' => 'Gayo', 'lname' => 'Papilleras', 'username' => 'jeniferpapilleras@yahoo.com', 'org_email' => 'jeniferpapilleras@yahoo.com'],
            ['emp_ID' => '1012-03C2', 'fname' => 'Grace Joy', 'mname' => 'Agustin', 'lname' => 'Peguit', 'username' => 'gracejoyagustin@gmail.com', 'org_email' => 'gracejoyagustin@gmail.com'],
            ['emp_ID' => '8811-38', 'fname' => 'Geno Quer', 'mname' => 'Temonio', 'lname' => 'Rodriguez', 'username' => 'genoquerrodriguez@gmail.com', 'org_email' => 'genoquerrodriguez@gmail.com'],
            ['emp_ID' => '1051-08', 'fname' => 'Franie', 'mname' => 'Cadiz', 'lname' => 'Rodriguez', 'username' => 'rodriguezfranie8@gmail.com', 'org_email' => 'rodriguezfranie8@gmail.com'],
            ['emp_ID' => '1091-03', 'fname' => 'Mary Rose', 'mname' => 'Abueva', 'lname' => 'Salabas', 'username' => 'mroseabueva05@gmail.com', 'org_email' => 'mroseabueva05@gmail.com'],
            ['emp_ID' => '4411-24', 'fname' => 'Shiela', 'mname' => 'Agustin', 'lname' => 'Salvoro', 'username' => 'shellasalvoro0210@gmail.com', 'org_email' => 'shellasalvoro0210@gmail.com'],
            // Duplicate emp_ID 8711-04 — overwrites Cadorna, Rosemar above.
            ['emp_ID' => '8711-04', 'fname' => 'Kevin Gil', 'mname' => 'Anfone', 'lname' => 'Socorro', 'username' => 'kevin25socorro@gmail.com', 'org_email' => 'kevin25socorro@gmail.com'],
            ['emp_ID' => '1101-010', 'fname' => 'Reynalyn', 'mname' => 'Dayang', 'lname' => 'Tan', 'username' => 'reynalyntan12@gmail.com', 'org_email' => 'reynalyntan12@gmail.com'],
            ['emp_ID' => '1061-19', 'fname' => 'Rufino', 'mname' => 'Clavero', 'lname' => 'Taytayan', 'username' => 'rufinotaytayan64@gmail.com', 'org_email' => 'rufinotaytayan64@gmail.com'],
            ['emp_ID' => '8711-18', 'fname' => 'Maria Teresa', 'mname' => 'Marcellana', 'lname' => 'Tobias', 'username' => 'mariateresatobias853@gmail.com', 'org_email' => 'mariateresatobias853@gmail.com'],
            ['emp_ID' => '8711-09', 'fname' => 'Robert', 'mname' => 'Camparisio', 'lname' => 'Tondo', 'username' => 'roberttondo1968@gmail.com', 'org_email' => 'roberttondo1968@gmail.com'],
            ['emp_ID' => '1081-09', 'fname' => 'Maricon', 'mname' => 'Hito', 'lname' => 'Toquero', 'username' => 'maricontoquero@gmail.com', 'org_email' => 'maricontoquero@gmail.com'],
            // Duplicate emp_ID 1091-05 — overwrites Barrera, Marlyn above.
            ['emp_ID' => '1091-05', 'fname' => 'Caroline Mae', 'mname' => 'Manlucot', 'lname' => 'Torres', 'username' => 'belleavis0@gmail.com', 'org_email' => 'belleavis0@gmail.com'],
            ['emp_ID' => '1101-05', 'fname' => 'Grace', 'mname' => 'Escala', 'lname' => 'Torres', 'username' => 'grashmeguapa@gmail.com', 'org_email' => 'grashmeguapa@gmail.com'],
            ['emp_ID' => '1011-36', 'fname' => 'Florjay', 'mname' => 'Lacadman', 'lname' => 'Ulpiana', 'username' => 'florjayulpiana87@gmail.com', 'org_email' => 'florjayulpiana87@gmail.com'],
            ['emp_ID' => '7611-04', 'fname' => 'Jensler', 'mname' => 'Cabugnason', 'lname' => 'Ulpiana', 'username' => 'jenzjou20@gmail.com', 'org_email' => 'jenzjou20@gmail.com'],
            ['emp_ID' => '7611-02', 'fname' => 'Ireen June', 'mname' => 'Balasabas', 'lname' => 'Vailoces', 'username' => 'junevailoces74@gmail.com', 'org_email' => 'junevailoces74@gmail.com'],
            ['emp_ID' => '1081-10', 'fname' => 'Irish', 'mname' => 'Amante', 'lname' => 'Valdez', 'username' => 'amanteirish02@gmail.com', 'org_email' => 'amanteirish02@gmail.com'],
            ['emp_ID' => '4411-22', 'fname' => 'Marivic', 'mname' => 'Sagun', 'lname' => 'Vallejo', 'username' => 'vallejomarivic208@gmail.com', 'org_email' => 'vallejomarivic208@gmail.com'],
            ['emp_ID' => '1071-02', 'fname' => 'Ruth', 'mname' => 'Dasian', 'lname' => 'Velarde', 'username' => 'ruthvelarde1972@gmail.com', 'org_email' => 'ruthvelarde1972@gmail.com'],
            ['emp_ID' => '1011-69', 'fname' => 'Steven Bryan', 'mname' => 'Tirambulo', 'lname' => 'Yuson', 'username' => 'y.stevenbryan@gmail.com', 'org_email' => 'y.stevenbryan@gmail.com'],
            ['emp_ID' => '1081-11', 'fname' => 'Maria Luna', 'mname' => 'Tirambulo', 'lname' => 'Yuson', 'username' => 'lunayuson@yahoo.com', 'org_email' => 'lunayuson@yahoo.com'],
            ['emp_ID' => '1011-227', 'fname' => 'Jeneben', 'mname' => null, 'lname' => 'Zuniega', 'username' => 'jenebenzoniega@yahoo.com', 'org_email' => 'jenebenzoniega@yahoo.com'],
        ];

        foreach ($regularEmployees as &$regular) {
            $regular['supervisor'] = null;
        }
        unset($regular);

        $people = array_merge($people, $regularEmployees);

        // The PDS page expects one row per section to already exist — the same
        // rows EmployeeController::empCreate() writes when HR adds an employee.
        $pdsSections = ['FamilyBg', 'EducBg', 'OtherInfo', 'InfoQuestion', 'PdsReference', 'GovId', 'OfficialTime'];

        foreach ($people as $person) {
            Employee::updateOrCreate(
                ['emp_ID' => $person['emp_ID']],
                array_merge([
                    'role' => 'employee',
                    'emp_status' => 1,       // Default Permanent
                    'emp_salary' => 0,
                    'stat_1' => 1,       // active
                    'dpn' => 0,       // data-privacy notice not yet accepted
                    'profile' => 'default.png',
                    'vl' => 15,
                    'sl' => 15,
                ], $person)
            );

            // Written directly, bypassing the model's creating() hook.
            DB::table('employees')
                ->where('emp_ID', $person['emp_ID'])
                ->update(['password' => Hash::make('password123')]);

            foreach ($pdsSections as $section) {
                $model = "App\\Models\\{$section}";
                $model::firstOrCreate(['empid' => $person['emp_ID']]);
            }
        }

        // The sample employee reports to the HR head.
        $hrHead = Employee::where('emp_ID', '2026-0003')->first();
        Employee::where('emp_ID', '2026-0004')->update(['supervisor' => $hrHead->id]);

        // Give each office a head so leave routing and office lists work.
        \App\Models\Office::where('id', 3)->update(['office_head_id' => Employee::where('emp_ID', '2026-0001')->value('id')]);
        \App\Models\Office::where('id', 4)->update(['office_head_id' => Employee::where('emp_ID', '2026-0002')->value('id')]);
        \App\Models\Office::where('id', 6)->update(['office_head_id' => $hrHead->id]);
    }
}
