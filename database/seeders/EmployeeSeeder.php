<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * The regular (permanent) personnel of LGU Mabinay, taken from
 * public/"HRIS EMPLOYEES - REGULAR .pdf".
 *
 * ONLY ROWS THAT CARRY AN EMPLOYEE ID NUMBER ARE SEEDED. emp_ID is what the
 * whole system keys an employee on — sign-in, DTR punches, leave credits and
 * the PDS all look a person up by it — so a row without one cannot be
 * represented. The source sheet lists 156 people but fills in an Employee ID
 * Number for only the 89 below; the other 67 (six of whom do have an e-mail
 * address) are left out until HR issues them an ID. The guard in run() also
 * refuses any row added here later without one.
 *
 * The sheet has no office or position column, so emp_dept and position are
 * left null for HR to fill in. Employment status is PERMANENT for every row,
 * which is emp_status 1.
 *
 * Sign-in name is the employee's e-mail address, and each new account is
 * issued config('auth.default_password'). That password is a placeholder, not
 * a credential — signing in with it works, but the account is held on the
 * change-password screen until a new one is set. It is written on insert only,
 * so re-running this seeder never resets a password an employee has since
 * chosen (same for leave balances and the data-privacy notice).
 */
class EmployeeSeeder extends Seeder
{
    public function run()
    {
        $regularEmployees = [
            ['emp_ID' => '8711-153', 'fname' => 'Clyde', 'mname' => 'Cataytay', 'lname' => 'Abendan', 'sex' => 'Male', 'username' => 'claudineabendan@gmail.com', 'org_email' => 'claudineabendan@gmail.com'],
            ['emp_ID' => '7611-01', 'fname' => 'Melba', 'mname' => 'Ramos', 'lname' => 'Abril', 'sex' => 'Female', 'username' => 'melbar.abril@yahoo.com', 'org_email' => 'melbar.abril@yahoo.com'],
            ['emp_ID' => '1021-14', 'fname' => 'Marjorie', 'mname' => 'Jamito', 'lname' => 'Abrio', 'sex' => 'Female', 'username' => 'marjorieabrio12@gmail.com', 'org_email' => 'marjorieabrio12@gmail.com'],
            ['emp_ID' => '8811-06', 'fname' => 'Ednalyn', 'mname' => 'Banong', 'lname' => 'Academia', 'sex' => 'Female', 'username' => 'ednalynacademia9@gmail.com', 'org_email' => 'ednalynacademia9@gmail.com'],
            ['emp_ID' => '1071-01', 'fname' => 'Mary Ann', 'mname' => 'Yuzon', 'lname' => 'Acaso', 'sex' => 'Female', 'username' => 'bebeth_acaso@yahoo.com', 'org_email' => 'bebeth_acaso@yahoo.com'],
            ['emp_ID' => '1071-12', 'fname' => 'Andre Jun', 'mname' => 'Quiliope', 'lname' => 'Agustin', 'sex' => 'Male', 'username' => 'andre.budgetoffice@gmail.com', 'org_email' => 'andre.budgetoffice@gmail.com'],
            ['emp_ID' => '8751-10', 'fname' => 'Isabelito', 'mname' => 'Emperado', 'lname' => 'Agustin', 'sex' => 'Male', 'username' => 'agustinisabelito9@gmail.com', 'org_email' => 'agustinisabelito9@gmail.com'],
            ['emp_ID' => '4411-31', 'fname' => 'Meraliza', 'mname' => 'Tejones', 'lname' => 'Alas-As', 'sex' => 'Female', 'username' => 'jemeraltejo@gmail.com', 'org_email' => 'jemeraltejo@gmail.com'],
            ['emp_ID' => '1081-06', 'fname' => 'Karen Lou', 'mname' => 'Mogar', 'lname' => 'Alindajao', 'sex' => 'Female', 'username' => 'karenloualindajao28@gmail.com', 'org_email' => 'karenloualindajao28@gmail.com'],
            ['emp_ID' => '1061-03', 'fname' => 'Chaild Mae', 'mname' => null, 'lname' => 'Amado', 'sex' => 'Female', 'username' => 'chaildmaeamado@gmail.com', 'org_email' => 'chaildmaeamado@gmail.com'],
            ['emp_ID' => '7611-03', 'fname' => 'Perla', 'mname' => 'Resentes', 'lname' => 'Amante', 'sex' => 'Female', 'username' => 'jenzyjau20@gmail.com', 'org_email' => 'jenzyjau20@gmail.com'],
            ['emp_ID' => '1012-01B', 'fname' => 'Dindo', 'mname' => 'Mana-Ay', 'lname' => 'Amorganda', 'sex' => 'Male', 'username' => 'adhinndz@yahoo.com', 'org_email' => 'adhinndz@yahoo.com'],
            ['emp_ID' => '1081-03', 'fname' => 'Karen Jean', 'mname' => 'Agor', 'lname' => 'Anfone-Lobos', 'sex' => 'Female', 'username' => 'kjanfone1118@gmail.com', 'org_email' => 'kjanfone1118@gmail.com'],
            ['emp_ID' => '1041-02', 'fname' => 'Brian', 'mname' => 'Durango', 'lname' => 'Ausejo', 'sex' => 'Male', 'username' => 'bausejo@yahoo.com', 'org_email' => 'bausejo@yahoo.com'],
            ['emp_ID' => '1091-13', 'fname' => 'Emelisa', 'mname' => 'Palma', 'lname' => 'Balderas', 'sex' => 'Female', 'username' => 'emelisabalderas1973@gmail.com', 'org_email' => 'emelisabalderas1973@gmail.com'],
            ['emp_ID' => '1101-04', 'fname' => 'Mishelle', 'mname' => 'Baldado', 'lname' => 'Baldoza', 'sex' => 'Female', 'username' => 'baldozamishelle@gmail.com', 'org_email' => 'baldozamishelle@gmail.com'],
            ['emp_ID' => '8711-08', 'fname' => 'Pablito', 'mname' => 'Gando', 'lname' => 'Baldoza', 'sex' => 'Male', 'username' => 'pablitobaldoza@gmail.com', 'org_email' => 'pablitobaldoza@gmail.com'],
            ['emp_ID' => '1091-09', 'fname' => 'Liniedo Jr.', 'mname' => 'Grapa', 'lname' => 'Banong', 'sex' => 'Male', 'username' => 'lgbanong@ymail.com', 'org_email' => 'lgbanong@ymail.com'],
            ['emp_ID' => '1091-05', 'fname' => 'Marlyn', 'mname' => 'Cagas', 'lname' => 'Barrera', 'sex' => 'Female', 'username' => 'marlynbarrera1973@gmail.com', 'org_email' => 'marlynbarrera1973@gmail.com'],
            ['emp_ID' => '1011-272', 'fname' => 'Nolriz', 'mname' => 'Caliso', 'lname' => 'Bitanghol', 'sex' => 'Male', 'username' => 'nolrizbong@gmail.com', 'org_email' => 'nolrizbong@gmail.com'],
            ['emp_ID' => '1071-10', 'fname' => 'Joseth', 'mname' => 'Pampilo', 'lname' => 'Buscato', 'sex' => 'Female', 'username' => 'budgetoffice.joseth@gmail.com', 'org_email' => 'budgetoffice.joseth@gmail.com'],
            ['emp_ID' => '1012-08C15', 'fname' => 'Michael', 'mname' => 'Emfat', 'lname' => 'Cabugnason', 'sex' => 'Male', 'username' => 'mikecabz80@gmail.com', 'org_email' => 'mikecabz80@gmail.com'],
            ['emp_ID' => '4411-16', 'fname' => 'Melanie', 'mname' => 'Sala', 'lname' => 'Cadalzo', 'sex' => 'Female', 'username' => 'melaniesalacadalzo@gmail.com', 'org_email' => 'melaniesalacadalzo@gmail.com'],
            ['emp_ID' => '1041-04', 'fname' => 'Rolly', 'mname' => 'Caandi', 'lname' => 'Cadalzo', 'sex' => 'Male', 'username' => 'rcadalzo30@gmail.com', 'org_email' => 'rcadalzo30@gmail.com'],
            ['emp_ID' => '1012-02C1', 'fname' => 'Vince Francis', 'mname' => 'Nocos', 'lname' => 'Cadayday', 'sex' => 'Male', 'username' => 'im84623@gmail.com', 'org_email' => 'im84623@gmail.com'],
            ['emp_ID' => '1051-04', 'fname' => 'Cicero', 'mname' => 'Ondoy', 'lname' => 'Cadiz', 'sex' => 'Male', 'username' => 'cicerocadiz345@gmail.com', 'org_email' => 'cicerocadiz345@gmail.com'],
            ['emp_ID' => '1081-02', 'fname' => 'Janice', 'mname' => 'Cadayona', 'lname' => 'Cadiz', 'sex' => 'Female', 'username' => 'janicecadayonacadiz@gmail.com', 'org_email' => 'janicecadayonacadiz@gmail.com'],
            ['emp_ID' => '8711-04', 'fname' => 'Rosemar', 'mname' => 'Lofrangco', 'lname' => 'Cadorna', 'sex' => 'Male', 'username' => 'rose.shai13@gmail.com', 'org_email' => 'rose.shai13@gmail.com'],
            ['emp_ID' => '8811-33', 'fname' => 'Normandy', 'mname' => 'Kabristante', 'lname' => 'Carpio Jr.', 'sex' => 'Male', 'username' => 'ydnamronkc@gmail.com', 'org_email' => 'ydnamronkc@gmail.com'],
            ['emp_ID' => '4411-07', 'fname' => 'Charlotte', 'mname' => 'Oro', 'lname' => 'Creer', 'sex' => 'Female', 'username' => 'charlottecreer330@gmail.com', 'org_email' => 'charlottecreer330@gmail.com'],
            ['emp_ID' => '1051-06', 'fname' => 'Amie Rose', 'mname' => 'Aba', 'lname' => 'Cueco', 'sex' => 'Female', 'username' => 'amierosecueco1980@gmail.com', 'org_email' => 'amierosecueco1980@gmail.com'],
            ['emp_ID' => '1051-02', 'fname' => 'Fritsie', 'mname' => 'Martinez', 'lname' => 'Dela Peña', 'sex' => 'Female', 'username' => 'stirf111@gmail.com', 'org_email' => 'stirf111@gmail.com'],
            ['emp_ID' => '1021-17', 'fname' => 'Azucenas', 'mname' => 'Narciso', 'lname' => 'Durango', 'sex' => 'Female', 'username' => 'azucenadurango73@gmail.com', 'org_email' => 'azucenadurango73@gmail.com'],
            ['emp_ID' => '4411-30', 'fname' => 'Myrla', 'mname' => 'Tumapa', 'lname' => 'Elnar', 'sex' => 'Female', 'username' => 'myrlatumapa@gmail.com', 'org_email' => 'myrlatumapa@gmail.com'],
            ['emp_ID' => '4411-29', 'fname' => 'Nelsie', 'mname' => 'Lobo', 'lname' => 'Escander', 'sex' => 'Female', 'username' => 'nelsiescander@gmail.com', 'org_email' => 'nelsiescander@gmail.com'],
            ['emp_ID' => '4411-38', 'fname' => 'Linalyn', 'mname' => 'Acaso', 'lname' => 'Ferrer', 'sex' => 'Female', 'username' => 'lynnferrer85@gmail.com', 'org_email' => 'lynnferrer85@gmail.com'],
            ['emp_ID' => '1011-259', 'fname' => 'Marie Apple Lorraine', 'mname' => 'Mission', 'lname' => 'Futalan', 'sex' => 'Female', 'username' => 'applelorrainef@gmail.com', 'org_email' => 'applelorrainef@gmail.com'],
            ['emp_ID' => '1051-01', 'fname' => 'Catalina', 'mname' => 'Ladesma', 'lname' => 'Garces', 'sex' => 'Female', 'username' => 'zardandy64@gmail.com', 'org_email' => 'zardandy64@gmail.com'],
            ['emp_ID' => '4411-08', 'fname' => 'Gayle Mae', 'mname' => 'Demetita', 'lname' => 'Garces', 'sex' => 'Female', 'username' => 'elyagmae@gmail.com', 'org_email' => 'elyagmae@gmail.com'],
            ['emp_ID' => '1012-04C3', 'fname' => 'Nico', 'mname' => 'Embang', 'lname' => 'Garces', 'sex' => 'Male', 'username' => 'garcesnico143@gmail.com', 'org_email' => 'garcesnico143@gmail.com'],
            ['emp_ID' => '1101-01', 'fname' => 'Bernadeth', 'mname' => 'Temonio', 'lname' => 'Guanzon', 'sex' => 'Female', 'username' => 'guanzonbernadeth1@gmail.com', 'org_email' => 'guanzonbernadeth1@gmail.com'],
            ['emp_ID' => '1021-16', 'fname' => 'Rodito', 'mname' => 'Teleron', 'lname' => 'Hermosada', 'sex' => 'Male', 'username' => 'hermosadarodito@gmail.com', 'org_email' => 'hermosadarodito@gmail.com'],
            ['emp_ID' => '1012-07C6', 'fname' => 'Joefrey', 'mname' => 'Custodio', 'lname' => 'Herrera', 'sex' => 'Male', 'username' => 'herrejean073@gmail.com', 'org_email' => 'herrejean073@gmail.com'],
            ['emp_ID' => '1061-06', 'fname' => 'Michael', 'mname' => 'Cataylo', 'lname' => 'Hongcuay', 'sex' => 'Male', 'username' => 'miekee102976@gmail.com', 'org_email' => 'miekee102976@gmail.com'],
            ['emp_ID' => '1091-06', 'fname' => 'Joseph', 'mname' => 'Ere', 'lname' => 'Hucal', 'sex' => 'Male', 'username' => 'josephhucal08@gmail.com', 'org_email' => 'josephhucal08@gmail.com'],
            ['emp_ID' => '7611-46', 'fname' => 'Anthony', 'mname' => 'De La Rosa', 'lname' => 'Ibero', 'sex' => 'Male', 'username' => 'anthony.ibero@gmail.com', 'org_email' => 'anthony.ibero@gmail.com'],
            ['emp_ID' => '1061-23', 'fname' => 'Junnah Rel', 'mname' => 'Caparida', 'lname' => 'Igpit', 'sex' => 'Female', 'username' => 'ijunnahrel@gmail.com', 'org_email' => 'ijunnahrel@gmail.com'],
            ['emp_ID' => '8711-16', 'fname' => 'Jenessa', 'mname' => 'Tongcua', 'lname' => 'Java', 'sex' => 'Female', 'username' => 'javajenessa@gmail.com', 'org_email' => 'javajenessa@gmail.com'],
            ['emp_ID' => '1101-02', 'fname' => 'Ivy', 'mname' => 'Villapando', 'lname' => 'Kadusale', 'sex' => 'Female', 'username' => 'ivykadusale7@gmail.com', 'org_email' => 'ivykadusale7@gmail.com'],
            ['emp_ID' => '8751-06', 'fname' => 'Jerelito', 'mname' => 'Jorolan', 'lname' => 'Lado', 'sex' => 'Male', 'username' => 'jerelitojorolanlado@gmail.com', 'org_email' => 'jerelitojorolanlado@gmail.com'],
            ['emp_ID' => '1071-03', 'fname' => 'Janice', 'mname' => 'Gantalao', 'lname' => 'Laluna', 'sex' => 'Female', 'username' => 'janice.budgetoffice@gmail.com', 'org_email' => 'janice.budgetoffice@gmail.com'],
            ['emp_ID' => '8711-06', 'fname' => 'Ira May', 'mname' => 'Cadalzo', 'lname' => 'Landiza', 'sex' => 'Female', 'username' => 'iracadalzo@gmail.com', 'org_email' => 'iracadalzo@gmail.com'],
            ['emp_ID' => '1101-03', 'fname' => 'Riche', 'mname' => 'Ejercito', 'lname' => 'Lastimoso', 'sex' => 'Female', 'username' => 'richelastimoso@gmail.com', 'org_email' => 'richelastimoso@gmail.com'],
            ['emp_ID' => '8711-148', 'fname' => 'Karen Joy', 'mname' => 'Cornelio', 'lname' => 'Lovina', 'sex' => 'Female', 'username' => 'karenjoylovina26@gmail.com', 'org_email' => 'karenjoylovina26@gmail.com'],
            ['emp_ID' => '8711-01', 'fname' => 'Lelanie', 'mname' => 'Anfone', 'lname' => 'Malacapay', 'sex' => 'Female', 'username' => 'laniemalacapay@gmail.com', 'org_email' => 'laniemalacapay@gmail.com'],
            ['emp_ID' => '1011-193', 'fname' => 'Apolinario', 'mname' => 'Parong', 'lname' => 'Mission', 'sex' => 'Male', 'username' => 'apolinariomission798@gmail.com', 'org_email' => 'apolinariomission798@gmail.com'],
            ['emp_ID' => '1091-07', 'fname' => 'Lolibeth', 'mname' => 'Baldoza', 'lname' => 'Narciso', 'sex' => 'Female', 'username' => 'lolibethnarciso13@gmail.com', 'org_email' => 'lolibethnarciso13@gmail.com'],
            ['emp_ID' => '1081-04', 'fname' => 'Mila Flor', 'mname' => 'Samonte', 'lname' => 'Nares', 'sex' => 'Female', 'username' => 'milaflornares@gmail.com', 'org_email' => 'milaflornares@gmail.com'],
            ['emp_ID' => '1061-01', 'fname' => 'Lucrecia', 'mname' => 'Carreon', 'lname' => 'Nicolas', 'sex' => 'Female', 'username' => 'luc31570@gmail.com', 'org_email' => 'luc31570@gmail.com'],
            ['emp_ID' => '1091-04', 'fname' => 'Mary Cel', 'mname' => 'Yuson', 'lname' => 'Niñal', 'sex' => 'Female', 'username' => 'marycelninal@gmail.com', 'org_email' => 'marycelninal@gmail.com'],
            ['emp_ID' => '4411-34', 'fname' => 'Chris Emmanuel', 'mname' => 'Jainar', 'lname' => 'Novera', 'sex' => 'Male', 'username' => 'chrisgame8888@gmail.co', 'org_email' => 'chrisgame8888@gmail.co'],
            ['emp_ID' => '1011-02', 'fname' => 'Ma. Rosario', 'mname' => 'Ferrer', 'lname' => 'Ocay', 'sex' => 'Female', 'username' => 'marosarioferrerocay@gmail.com', 'org_email' => 'marosarioferrerocay@gmail.com'],
            ['emp_ID' => '1081-05', 'fname' => 'Marissa', 'mname' => 'Dela Peña', 'lname' => 'Ojeda', 'sex' => 'Female', 'username' => 'marissaojeda54@gmail.com', 'org_email' => 'marissaojeda54@gmail.com'],
            ['emp_ID' => '1061-20', 'fname' => 'Genevieve', 'mname' => 'Cañete', 'lname' => 'Omandac', 'sex' => 'Female', 'username' => 'vieveomandac@gmail.com', 'org_email' => 'vieveomandac@gmail.com'],
            ['emp_ID' => '1011-19', 'fname' => 'James', 'mname' => 'Vergara', 'lname' => 'Ones', 'sex' => 'Male', 'username' => 'gawix_james@yahoo.com', 'org_email' => 'gawix_james@yahoo.com'],
            ['emp_ID' => '8811-02', 'fname' => 'Elisa', 'mname' => 'Baldado', 'lname' => 'Pancho', 'sex' => 'Female', 'username' => 'elizapancho@gmail.com', 'org_email' => 'elizapancho@gmail.com'],
            ['emp_ID' => '1021-13', 'fname' => 'Jenifer', 'mname' => 'Gayo', 'lname' => 'Papilleras', 'sex' => 'Female', 'username' => 'jeniferpapilleras@yahoo.com', 'org_email' => 'jeniferpapilleras@yahoo.com'],
            ['emp_ID' => '1012-03C2', 'fname' => 'Grace Joy', 'mname' => 'Agustin', 'lname' => 'Peguit', 'sex' => 'Female', 'username' => 'gracejoyagustin@gmail.com', 'org_email' => 'gracejoyagustin@gmail.com'],
            ['emp_ID' => '1051-08', 'fname' => 'Franie', 'mname' => 'Cadiz', 'lname' => 'Rodriguez', 'sex' => 'Male', 'username' => 'rodriguezfranie8@gmail.com', 'org_email' => 'rodriguezfranie8@gmail.com'],
            ['emp_ID' => '8811-38', 'fname' => 'Geno Quer', 'mname' => 'Temonio', 'lname' => 'Rodriguez', 'sex' => 'Male', 'username' => 'genoquerrodriguez@gmail.com', 'org_email' => 'genoquerrodriguez@gmail.com'],
            ['emp_ID' => '1091-03', 'fname' => 'Mary Rose', 'mname' => 'Abueva', 'lname' => 'Salabas', 'sex' => 'Female', 'username' => 'mroseabueva05@gmail.com', 'org_email' => 'mroseabueva05@gmail.com'],
            ['emp_ID' => '4411-24', 'fname' => 'Shiela', 'mname' => 'Agustin', 'lname' => 'Salvoro', 'sex' => 'Female', 'username' => 'shellasalvoro0210@gmail.com', 'org_email' => 'shellasalvoro0210@gmail.com'],
            ['emp_ID' => '8711-04', 'fname' => 'Kevin Gil', 'mname' => 'Anfone', 'lname' => 'Socorro', 'sex' => 'Male', 'username' => 'kevin25socorro@gmail.com', 'org_email' => 'kevin25socorro@gmail.com'],
            ['emp_ID' => '1101-010', 'fname' => 'Reynalyn', 'mname' => 'Dayang', 'lname' => 'Tan', 'sex' => 'Female', 'username' => 'reynalyntan12@gmail.com', 'org_email' => 'reynalyntan12@gmail.com'],
            ['emp_ID' => '1061-19', 'fname' => 'Rufino', 'mname' => 'Clavero', 'lname' => 'Taytayan', 'sex' => 'Male', 'username' => 'rufinotaytayan64@gmail.com', 'org_email' => 'rufinotaytayan64@gmail.com'],
            ['emp_ID' => '8711-18', 'fname' => 'Maria Teresa', 'mname' => 'Marcellana', 'lname' => 'Tobias', 'sex' => 'Female', 'username' => 'mariateresatobias853@gmail.com', 'org_email' => 'mariateresatobias853@gmail.com'],
            ['emp_ID' => '8711-09', 'fname' => 'Robert', 'mname' => 'Camparisio', 'lname' => 'Tondo', 'sex' => 'Female', 'username' => 'roberttondo1968@gmail.com', 'org_email' => 'roberttondo1968@gmail.com'],
            ['emp_ID' => '1081-09', 'fname' => 'Maricon', 'mname' => 'Hito', 'lname' => 'Toquero', 'sex' => 'Female', 'username' => 'maricontoquero@gmail.com', 'org_email' => 'maricontoquero@gmail.com'],
            ['emp_ID' => '1091-05', 'fname' => 'Caroline Mae', 'mname' => 'Manlucot', 'lname' => 'Torres', 'sex' => 'Female', 'username' => 'belleavis0@gmail.com', 'org_email' => 'belleavis0@gmail.com'],
            ['emp_ID' => '1101-05', 'fname' => 'Grace', 'mname' => 'Escala', 'lname' => 'Torres', 'sex' => 'Female', 'username' => 'grashmeguapa@gmail.com', 'org_email' => 'grashmeguapa@gmail.com'],
            ['emp_ID' => '1011-36', 'fname' => 'Florjay', 'mname' => 'Lacadman', 'lname' => 'Ulpiana', 'sex' => 'Female', 'username' => 'florjayulpiana87@gmail.com', 'org_email' => 'florjayulpiana87@gmail.com'],
            ['emp_ID' => '7611-04', 'fname' => 'Jensler', 'mname' => 'Cabugnason', 'lname' => 'Ulpiana', 'sex' => 'Female', 'username' => 'jenzjou20@gmail.com', 'org_email' => 'jenzjou20@gmail.com'],
            ['emp_ID' => '7611-02', 'fname' => 'Ireen June', 'mname' => 'Balasabas', 'lname' => 'Vailoces', 'sex' => 'Female', 'username' => 'junevailoces74@gmail.com', 'org_email' => 'junevailoces74@gmail.com'],
            ['emp_ID' => '1081-10', 'fname' => 'Irish', 'mname' => 'Amante', 'lname' => 'Valdez', 'sex' => 'Female', 'username' => 'amanteirish02@gmail.com', 'org_email' => 'amanteirish02@gmail.com'],
            ['emp_ID' => '4411-22', 'fname' => 'Marivic', 'mname' => 'Sagun', 'lname' => 'Vallejo', 'sex' => 'Female', 'username' => 'vallejomarivic208@gmail.com', 'org_email' => 'vallejomarivic208@gmail.com'],
            ['emp_ID' => '1071-02', 'fname' => 'Ruth', 'mname' => 'Dasian', 'lname' => 'Velarde', 'sex' => 'Female', 'username' => 'ruthvelarde1972@gmail.com', 'org_email' => 'ruthvelarde1972@gmail.com'],
            ['emp_ID' => '1081-11', 'fname' => 'Maria Luna', 'mname' => 'Tirambulo', 'lname' => 'Yuson', 'sex' => 'Female', 'username' => 'lunayuson@yahoo.com', 'org_email' => 'lunayuson@yahoo.com'],
            ['emp_ID' => '1011-69', 'fname' => 'Steven Bryan', 'mname' => 'Tirambulo', 'lname' => 'Yuson', 'sex' => 'Male', 'username' => 'y.stevenbryan@gmail.com', 'org_email' => 'y.stevenbryan@gmail.com'],
            ['emp_ID' => '1011-227', 'fname' => 'Jeneben', 'mname' => null, 'lname' => 'Zuniega', 'sex' => 'Male', 'username' => 'jenebenzoniega@yahoo.com', 'org_email' => 'jenebenzoniega@yahoo.com'],
        ];

        // The PDS page expects one row per section to already exist — the same
        // rows EmployeeController::empCreate() writes when HR adds an employee.
        $pdsSections = ['FamilyBg', 'EducBg', 'OtherInfo', 'InfoQuestion', 'PdsReference', 'GovId', 'OfficialTime'];

        $seeded     = [];
        $noId       = [];
        $duplicates = [];

        foreach ($regularEmployees as $person) {
            $empId = trim((string) ($person['emp_ID'] ?? ''));

            // No Employee ID Number, no record.
            if ($empId === '') {
                $noId[] = "{$person['lname']}, {$person['fname']}";
                continue;
            }

            // emp_ID is the lookup key, so two people cannot share one. The
            // source sheet does list two IDs twice (1091-05 and 8711-04); the
            // first row wins and the clash is reported below rather than
            // silently overwriting the earlier person.
            if (isset($seeded[$empId])) {
                $duplicates[] = "{$empId}: kept {$seeded[$empId]}, skipped {$person['lname']}, {$person['fname']}";
                continue;
            }
            $seeded[$empId] = "{$person['lname']}, {$person['fname']}";

            $person['emp_ID'] = $empId;
            $employee = Employee::firstOrNew(['emp_ID' => $empId]);

            if (! $employee->exists) {
                // Written once, on insert. Everything here is afterwards owned
                // by the employee or by HR, so a re-run must not touch it.
                $employee->fill([
                    'role'       => 'employee',
                    'emp_status' => 1,              // Permanent
                    'emp_salary' => 0,
                    'stat_1'     => 1,              // active
                    'dpn'        => 0,              // privacy notice not yet accepted
                    'profile'    => 'default.png',
                    'vl'         => 15,
                    'sl'         => 15,
                    // Employee::boot() hashes this on `creating`.
                    'password'   => config('auth.default_password'),
                ]);
            }

            // Identity comes from the PDF and is refreshed on every run.
            $employee->fill($person)->save();

            foreach ($pdsSections as $section) {
                $model = "App\\Models\\{$section}";
                $model::firstOrCreate(['empid' => $empId]);
            }
        }

        $this->command?->info(sprintf('Employees seeded: %d of %d rows.', count($seeded), count($regularEmployees)));

        foreach ($noId as $person) {
            $this->command?->warn("Skipped (no Employee ID Number): {$person}");
        }

        foreach ($duplicates as $clash) {
            $this->command?->warn("Duplicate Employee ID Number in the source sheet — {$clash}");
        }
    }
}
