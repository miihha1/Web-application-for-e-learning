import { nextTick, ref, watch } from 'vue';

export type Locale = 'sk' | 'en';

const storageKey = 'app_locale';
const legacyStorageKey = 'welcome_locale';
const locale = ref<Locale>('sk');
let initialized = false;
let observer: MutationObserver | null = null;

const textTranslations: Record<string, string> = {
    // Navigation and common actions
    'Prehľad': 'Dashboard',
    'PrehДѕad': 'Dashboard',
    Prehlad: 'Dashboard',
    Dashboard: 'Dashboard',
    Kurzy: 'Courses',
    'Vytvoriť kurz': 'Create course',
    'VytvoriЕҐ kurz': 'Create course',
    'Aktivovať učiteľa': 'Activate teacher',
    'AktivovaЕҐ uДЌiteДѕa': 'Activate teacher',
    'Späť': 'Back',
    '← Späť': '← Back',
    '← Späť na úvod': '← Back to home',
    '← Späť ku kurzom': '← Back to courses',
    '← Späť ku kurzu': '← Back to course',
    '← Späť do správy': '← Back to management',
    'Otvoriť kurzy ->': 'Open courses ->',
    'Otvoriť kurz ->': 'Open course ->',
    'Otvoriť ->': 'Open ->',
    'Pokračovať ->': 'Continue ->',
    'Otvoriť správu ->': 'Open management ->',
    'Otvoriť správu →': 'Open management →',
    Uložiť: 'Save',
    'UloЕѕiЕҐ': 'Save',
    Pridať: 'Add',
    'PridaЕҐ': 'Add',
    Upraviť: 'Edit',
    'UpraviЕҐ': 'Edit',
    Odstrániť: 'Delete',
    'OdstrГЎniЕҐ': 'Delete',
    Vytvoriť: 'Create',
    'VytvoriЕҐ': 'Create',
    'Odhlásiť sa': 'Unenroll',
    'Zapísať sa': 'Enroll',
    'Zobraziť kurzy': 'View courses',
    'ZobraziЕҐ kurzy': 'View courses',

    // Auth pages
    'Prihláste sa do účtu': 'Log in to your account',
    'Zadajte svoj e-mail a heslo na prihlásenie': 'Enter your email and password to log in',
    'Zapamätať si ma': 'Remember me',
    'Nemáte účet?': "Don't have an account?",
    'Registrovať sa': 'Register',
    'Prihlásiť sa cez Google': 'Log in with Google',
    'Zadajte svoje údaje na vytvorenie účtu': 'Enter your details to create an account',
    'Celé meno': 'Full name',
    'Potvrdiť heslo': 'Confirm password',
    'Už máte účet?': 'Already have an account?',
    'Pokračovať cez Google': 'Continue with Google',
    'PrihlГЎste sa do ГєДЌtu': 'Log in to your account',
    'Zadajte svoj e-mail a heslo na prihlГЎsenie': 'Enter your email and password to log in',
    'Zabudli ste heslo?': 'Forgot password?',
    'ZapamГ¤taЕҐ si ma': 'Remember me',
    'NemГЎte ГєДЌet?': "Don't have an account?",
    'RegistrovaЕҐ sa': 'Register',
    alebo: 'or',
    'PrihlГЎsiЕҐ sa cez Google': 'Log in with Google',
    'Zadajte svoje Гєdaje na vytvorenie ГєДЌtu': 'Enter your details to create an account',
    'CelГ© meno': 'Full name',
    'PotvrdiЕҐ heslo': 'Confirm password',
    'UЕѕ mГЎte ГєДЌet?': 'Already have an account?',
    'PokraДЌovaЕҐ cez Google': 'Continue with Google',

    // Welcome
    'Webová aplikácia pre e-learning': 'Web application for e-learning',
    'WebovГЎ aplikГЎcia pre e-learning': 'Web application for e-learning',
    'Bakalárska práca • pracovný prototyp': 'Bachelor thesis • working prototype',
    'BakalГЎrska prГЎca вЂў pracovnГЅ prototyp': 'Bachelor thesis • working prototype',
    'Prihlásenie': 'Log in',
    'PrihlГЎsenie': 'Log in',
    Registrácia: 'Register',
    'RegistrГЎcia': 'Register',
    'Kurzy • lekcie • testy': 'Courses • lessons • tests',
    'Kurzy вЂў lekcie вЂў testy': 'Courses • lessons • tests',
    'Študuj online bez zbytočného šumu.': 'Study online without unnecessary noise.',
    'Е tuduj online bez zbytoДЌnГ©ho ЕЎumu.': 'Study online without unnecessary noise.',
    'Zrozumiteľná platforma pre kurzy, lekcie a záverečné testy. Intuitívne rozhranie, prehľadný progres a rýchly štart.':
        'A clear platform for courses, lessons, and final tests. Intuitive interface, visible progress, and a fast start.',
    'ZrozumiteДѕnГЎ platforma pre kurzy, lekcie a zГЎvereДЌnГ© testy. IntuitГ­vne rozhranie, prehДѕadnГЅ progres a rГЅchly ЕЎtart.':
        'A clear platform for courses, lessons, and final tests. Intuitive interface, visible progress, and a fast start.',
    'Začať teraz': 'Start now',
    'ZaДЌaЕҐ teraz': 'Start now',
    'Prihlásiť sa': 'Log in',
    'PrihlГЎsiЕҐ sa': 'Log in',
    'Prejsť do prehľadu': 'Go to dashboard',
    'PrejsЕҐ do prehДѕadu': 'Go to dashboard',
    Moduly: 'Modules',
    'Kurzy a lekcie': 'Courses and lessons',
    Progres: 'Progress',
    'Jasný prehľad': 'Clear overview',
    'JasnГЅ prehДѕad': 'Clear overview',
    Testy: 'Tests',
    'Rýchla kontrola': 'Quick check',
    'RГЅchla kontrola': 'Quick check',
    'Ako to funguje': 'How it works',
    'Vyberte si kurz a prejdite lekcie v správnom poradí.':
        'Choose a course and complete the lessons in the correct order.',
    'Vyberte si kurz a prejdite lekcie v sprГЎvnom poradГ­.':
        'Choose a course and complete the lessons in the correct order.',
    'Sledujte progres a vracajte sa k náročným témam.':
        'Track your progress and return to topics that need more practice.',
    'Sledujte progres a vracajte sa k nГЎroДЌnГЅm tГ©mam.':
        'Track your progress and return to topics that need more practice.',
    'Absolvujte test a ihneď získate výsledok aj históriu.':
        'Complete the test and immediately see your result and history.',
    'Absolvujte test a ihneДЏ zГ­skate vГЅsledok aj histГіriu.':
        'Complete the test and immediately see your result and history.',
    'Pre študentov': 'For students',
    'Pre ЕЎtudentov': 'For students',
    'Jednoduchá navigácia': 'Simple navigation',
    'JednoduchГЎ navigГЎcia': 'Simple navigation',
    'Bez zbytočností. Len to potrebné.': 'No clutter. Only what you need.',
    'Bez zbytoДЌnostГ­. Len to potrebnГ©.': 'No clutter. Only what you need.',
    'Pre učiteľov': 'For teachers',
    'Pre uДЌiteДѕov': 'For teachers',
    'Štruktúra kurzu': 'Course structure',
    'Е truktГєra kurzu': 'Course structure',
    'Pohodlná správa lekcií a testov.': 'Convenient management of lessons and tests.',
    'PohodlnГЎ sprГЎva lekciГ­ a testov.': 'Convenient management of lessons and tests.',
    Tip: 'Tip',
    'Po prihlásení je k dispozícii prehľad s rýchlym zhrnutím.':
        'After logging in, the dashboard provides a quick summary.',
    'Po prihlГЎsenГ­ je k dispozГ­cii prehДѕad s rГЅchlym zhrnutГ­m.':
        'After logging in, the dashboard provides a quick summary.',

    // Dashboard and courses
    'Rýchly prehľad kurzov, progresu a testov. Pokračujte tam, kde ste skončili.':
        'Quick overview of courses, progress, and tests. Continue where you left off.',
    'Moje kurzy': 'My courses',
    'Aktívne zápisy': 'Active enrollments',
    'Dokončené lekcie': 'Completed lessons',
    'Posledné pokusy': 'Latest attempts',
    'Posledné lekcie': 'Latest lessons',
    'Najbližší test': 'Next test',
    'Vyberte si kurz a prechádzajte lekcie krok za krokom. Na konci je záverečný test.':
        'Choose a course and go through the lessons step by step. A final test is available at the end.',
    Spolu: 'Total',
    'Všetky kurzy': 'All courses',
    'Otvorený': 'Open',
    'S kódom': 'With code',
    'Zapísaný': 'Enrolled',
    'Bez popisu': 'No description',
    'Pre váš dopyt nie sú žiadne kurzy.': 'No courses match your search.',
    'Zoznam kurzov, ktoré spravujete alebo ste vytvorili.':
        'Courses you manage or created.',
    'Prehľad vašich kurzov, progresu a rýchly prístup k lekciám.':
        'Overview of your courses, progress, and quick access to lessons.',
    Všetky: 'All',
    'V procese': 'In progress',
    Dokončené: 'Completed',
    Nezačaté: 'Not started',
    'Pre zvolený filter nie sú žiadne kurzy.': 'There are no courses for the selected filter.',
    Lekcií: 'Lessons',
    Autor: 'Author',
    Kurz: 'Course',
    'Kód kurzu': 'Course code',
    'Zápis do kurzu': 'Course enrollment',
    'Tento kurz je dostupný iba zapísaným študentom.':
        'This course is available only to enrolled students.',
    'K zápisu do kurzu sa musíte prihlásiť ako študent.':
        'You must log in as a student to enroll in this course.',
    'Najprv sa zapíšte do kurzu, aby ste videli obsah.':
        'Enroll in the course first to see the content.',
    'Zatiaľ nie sú žiadne lekcie.': 'There are no lessons yet.',
    Zhrnutie: 'Summary',
    Typ: 'Type',
    'Odovzdajte tento kód študentom na zápis.':
        'Share this code with students so they can enroll.',
    'Správa kurzu': 'Course management',
    'Záverečný test': 'Final test',
    'Overte si vedomosti z kurzu': 'Check your course knowledge',
    'Po dokončení uvidíte výsledok a históriu pokusov.':
        'After finishing, you will see your result and attempt history.',
    'Test zatiaľ nie je pripravený.': 'The test is not ready yet.',
    'Ste zapísaní': 'You are enrolled',

    // Teacher/course management
    'Štatistika kurzu': 'Course statistics',
    'Prehľad študentov a testov': 'Overview of students and tests',
    'Zapísaní študenti': 'Enrolled students',
    'Pokusy testu': 'Test attempts',
    'Študenti s testom': 'Students with test',
    'Priemerná úspešnosť': 'Average success rate',
    Študent: 'Student',
    Test: 'Test',
    'Zatiaľ nie sú zapísaní žiadni študenti.': 'No students are enrolled yet.',
    splnené: 'passed',
    nesplnené: 'failed',
    'Bez pokusu': 'No attempt',
    'Najčastejšie nesprávne otázky': 'Most frequent wrong questions',
    'Zatiaľ nie sú dostupné odpovede.': 'No answers are available yet.',
    Nesprávne: 'Wrong',
    'Detaily kurzu': 'Course details',
    'Názov kurzu': 'Course title',
    'Titulný obrázok kurzu': 'Course cover image',
    'Odstrániť aktuálny obrázok': 'Remove current image',
    'Popis kurzu': 'Course description',
    'Uložiť detaily': 'Save details',
    'Prístup ku kurzu': 'Course access',
    'Otvorený prístup (bez kódu)': 'Open access (without code)',
    'Prístup s kódom': 'Access with code',
    'Uložiť prístup': 'Save access',
    'Najprv uložte zmenu prístupu, potom bude možné vygenerovať nový kód.':
        'Save the access change first, then you can generate a new code.',
    'Vygenerovať nový kód': 'Generate new code',
    'Nová lekcia': 'New lesson',
    'Zatiaľ nie sú vytvorené žiadne lekcie.': 'No lessons have been created yet.',
    'Názov testu': 'Test title',
    'Popis testu': 'Test description',
    'Percento úspešnosti': 'Passing percentage',
    'Limit minút': 'Minute limit',
    'Max. pokusov': 'Max attempts',
    'Pauza minút': 'Cooldown minutes',
    'Náhodné poradie otázok': 'Random question order',
    'Náhodné poradie odpovedí': 'Random answer order',
    'Uložiť test': 'Save test',
    Otázky: 'Questions',
    'Pridať otázku ->': 'Add question ->',
    správne: 'correct',
    Správna: 'Correct',
    'Upraviť lekciu': 'Edit lesson',
    'Vytvoriť lekciu': 'Create lesson',
    Názov: 'Title',
    'NГЎzov': 'Title',
    'Obsah lekcie': 'Lesson content',
    Poradie: 'Order',
    'Doplnkové materiály': 'Additional materials',
    'PDF, ZIP, DOCX, externé odkazy alebo video k lekcii.':
        'PDF, ZIP, DOCX, external links, or lesson video.',
    'Názov materiálu': 'Material title',
    Súbor: 'File',
    Odkaz: 'Link',
    'Zatiaľ nie sú pridané žiadne materiály.': 'No materials have been added yet.',
    'Upraviť otázku': 'Edit question',
    'Nová otázka': 'New question',
    'Text otázky': 'Question text',
    'Napríklad: Ktoré značky patria do HTML?': 'For example: Which tags belong to HTML?',
    'Označte jeden alebo viac správnych variantov. Počet možností si môžete pridávať alebo odoberať.':
        'Mark one or more correct options. You can add or remove answer options.',
    'Možnosti odpovede': 'Answer options',
    'Pridať možnosť': 'Add option',
    Možnosť: 'Option',

    // Tests and lessons
    'Výsledky testu': 'Test results',
    'Spustiť test': 'Start test',
    Zostáva: 'Remaining',
    Inštrukcie: 'Instructions',
    'Pri niektorých otázkach môže byť správnych viac odpovedí. Po odoslaní budete presmerovaní na samostatnú stránku výsledkov.':
        'Some questions can have more than one correct answer. After submitting, you will be redirected to a separate results page.',
    'Hranica úspešnosti': 'Passing threshold',
    Limit: 'Limit',
    Pokusy: 'Attempts',
    Pauza: 'Cooldown',
    'Vyberte všetky správne odpovede.': 'Select all correct answers.',
    'Vyberte jednu správnu odpoveď.': 'Select one correct answer.',
    'Odoslať test': 'Submit test',
    'Posledný výsledok': 'Latest result',
    Splnené: 'Passed',
    Nesplnené: 'Failed',
    'Tento test ešte nemá žiadny výsledok.': 'This test has no result yet.',
    'Časový limit': 'Time limit',
    'Pauza medzi pokusmi': 'Cooldown between attempts',
    'História pokusov': 'Attempt history',
    'Zatiaľ nie sú uložené žiadne pokusy.': 'No attempts have been saved yet.',
    Pokus: 'Attempt',
    'Rozbor posledného pokusu': 'Latest attempt analysis',
    Správne: 'Correct',
    Vybrané: 'Selected',
    'V tomto kurze zatiaľ nie sú pridané žiadne otázky.':
        'No questions have been added to this course yet.',
    'Test ešte nie je pripravený': 'The test is not ready yet',
    'V tejto lekcii zatiaľ nie je obsah.': 'This lesson has no content yet.',

    // Auth/settings
    'Vytvoriť účet': 'Create account',
    'Zadajte kód od administrátora.': 'Enter the administrator code.',
    Nastavenia: 'Settings',
    'Spravujte nastavenia profilu a účtu': 'Manage your profile and account settings',
    Profil: 'Profile',
    Heslo: 'Password',
    'Dvojfaktorové overenie': 'Two-factor authentication',
    Vzhľad: 'Appearance',
    'Profilové informácie': 'Profile information',
    'Aktualizujte svoje meno a e-mailovú adresu': 'Update your name and email address',
    Meno: 'Name',
    'E-mailová adresa': 'Email address',
    'Odstrániť účet': 'Delete account',
    Upozornenie: 'Warning',
};

const phraseTranslations: Array<[string, string]> = [
    ['← Späť do prehľadu', '← Back to dashboard'],
    ['Vyplňte základné informácie o kurze a pripravte úvodnú vizuálnu kartu.', 'Fill in the basic course information and prepare the introductory visual card.'],
    ['Napríklad: Základy HTML', 'For example: HTML basics'],
    ['Titulný obrázok', 'Cover image'],
    ['Titulný obrázok kurzu', 'Course cover image'],
    ['Možnosť', 'Option'],
    ['Platforma', 'Platform'],
    ['Sekcie', 'Sections'],
    ['Dvojfaktorová ochrana', 'Two-factor protection'],
    ['Informácie o profile', 'Profile information'],
    ['Správa profilu a nastavení účtu', 'Manage profile and account settings'],
    ['Odstráni váš účet a všetky jeho zdroje', 'Deletes your account and all of its resources'],
    ['Aktualizovať heslo', 'Update password'],
    ['Uistite sa, že váš účet používa dlhé, náhodné heslo, aby bol chránený', 'Make sure your account uses a long, random password to stay protected'],
    ['Súčasné heslo', 'Current password'],
    ['Nové heslo', 'New password'],
    ['Potvrdiť heslo', 'Confirm password'],
    ['Uložiť heslo', 'Save password'],
    ['Nastavenie vzhľadu', 'Appearance settings'],
    ['Nastavenie profilu', 'Profile settings'],
    ['Nastavenie hesla', 'Password settings'],
    ['Aktualizujte nastavenia vzhľadu účtu', 'Update your account appearance settings'],
    ['Svetlý', 'Light'],
    ['Tmavý', 'Dark'],
    ['Systém', 'System'],
    ['Potvrďte heslo', 'Confirm password'],
    ['Toto je zabezpečená časť aplikácie. Pred pokračovaním potvrďte svoje heslo.', 'This is a secure area of the application. Confirm your password before continuing.'],
    ['Moje vytvorené kurzy', 'My created courses'],
    ['Spolu:', 'Total:'],
    ['Hľadať kurz podľa názvu...', 'Search course by title...'],
    ['Hľadať kurz...', 'Search course...'],
    ['Lekcií:', 'Lessons:'],
    ['Lekcie', 'Lessons'],
    ['Kurz:', 'Course:'],
    ['Typ:', 'Type:'],
    ['Zapísaní študenti:', 'Enrolled students:'],
    ['Nesprávne:', 'Wrong:'],
    ['Spustiť test ->', 'Start test ->'],
    ['Spustiť test', 'Start test'],
    ['Overte si vedomosti a pozrite si výsledky.', 'Check your knowledge and view your results.'],
    ['Hranica úspešnosti:', 'Passing threshold:'],
    ['Pokusy:', 'Attempts:'],
    ['Pauza:', 'Cooldown:'],
    ['Dosiahli ste maximálny počet pokusov.', 'You have reached the maximum number of attempts.'],
    ['Správa kurzu:', 'Course management:'],
    ['Štartovací balík Laravel', 'E-learning'],

    // Mojibake variants still present in a few templates.
    ['Moje vytvorenГ© kurzy', 'My created courses'],
    ['HДѕadaЕҐ kurz podДѕa nГЎzvu...', 'Search course by title...'],
    ['HДѕadaЕҐ kurz...', 'Search course...'],
    ['LekciГ­:', 'Lessons:'],
    ['SpustiЕҐ test ->', 'Start test ->'],
    ['SpustiЕҐ test', 'Start test'],
    ['Hranica ГєspeЕЎnosti:', 'Passing threshold:'],
    ['Dosiahli ste maximГЎlny poДЌet pokusov.', 'You have reached the maximum number of attempts.'],
    ['ZapГ­sanГ­ ЕЎtudenti:', 'Enrolled students:'],
    ['NesprГЎvne:', 'Wrong:'],
    ['SprГЎva kurzu:', 'Course management:'],
    ['Е tartovacГ­ balГ­k Laravel', 'E-learning'],
];

const attrTranslations = textTranslations;
const originals = new WeakMap<Text, string>();
const attrOriginals = new WeakMap<Element, Map<string, string>>();

function initialLocale(): Locale {
    if (typeof window === 'undefined') {
        return 'sk';
    }

    const stored =
        window.localStorage.getItem(storageKey) ??
        window.localStorage.getItem(legacyStorageKey);

    return stored === 'en' || stored === 'sk' ? stored : 'sk';
}

function leadingWhitespace(value: string): string {
    return value.match(/^\s*/)?.[0] ?? '';
}

function trailingWhitespace(value: string): string {
    return value.match(/\s*$/)?.[0] ?? '';
}

function translateString(value: string): string {
    const key = value.trim();
    const translated = textTranslations[key];

    if (translated) {
        return `${leadingWhitespace(value)}${translated}${trailingWhitespace(value)}`;
    }

    let result = value;

    for (const [source, target] of phraseTranslations) {
        result = result.replaceAll(source, target);
    }

    return result;
}

function shouldSkip(node: Node): boolean {
    const parent = node.parentElement;

    if (!parent) {
        return true;
    }

    if (parent.closest('[data-i18n-skip], .rich-editor, .ProseMirror, [contenteditable="true"]')) {
        return true;
    }

    if (parent.closest('[data-i18n-managed]')) {
        return true;
    }

    return ['SCRIPT', 'STYLE', 'TEXTAREA', 'CODE', 'PRE'].includes(parent.tagName);
}

function translateTextNode(node: Text): void {
    if (shouldSkip(node) || !node.nodeValue?.trim()) {
        return;
    }

    if (!originals.has(node)) {
        originals.set(node, node.nodeValue);
    }

    const original = originals.get(node) ?? node.nodeValue;
    node.nodeValue = locale.value === 'en' ? translateString(original) : original;
}

function translateAttributes(element: Element): void {
    if (element.closest('[data-i18n-skip], .rich-editor, .ProseMirror, [contenteditable="true"]')) {
        return;
    }

    if (element.closest('[data-i18n-managed]')) {
        return;
    }

    const attrs = ['placeholder', 'title', 'aria-label'];
    let saved = attrOriginals.get(element);

    for (const attr of attrs) {
        const value = element.getAttribute(attr);

        if (!value) {
            continue;
        }

        if (!saved) {
            saved = new Map<string, string>();
            attrOriginals.set(element, saved);
        }

        if (!saved.has(attr)) {
            saved.set(attr, value);
        }

        const original = saved.get(attr) ?? value;
        element.setAttribute(attr, locale.value === 'en' ? translateString(original) : original);
    }
}

export function applyLanguage(root: ParentNode = document.body): void {
    if (typeof document === 'undefined' || !root) {
        return;
    }

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let current = walker.nextNode();

    while (current) {
        translateTextNode(current as Text);
        current = walker.nextNode();
    }

    const elementRoot = root instanceof Element ? root : document.body;
    translateAttributes(elementRoot);
    elementRoot.querySelectorAll('*').forEach(translateAttributes);
}

export function setLocale(value: Locale): void {
    locale.value = value;

    if (typeof window !== 'undefined') {
        window.localStorage.setItem(storageKey, value);
        window.localStorage.setItem(legacyStorageKey, value);
    }
}

export function useLanguage() {
    return {
        locale,
        setLocale,
        isEnglish: () => locale.value === 'en',
    };
}

export function initializeLanguage(): void {
    if (initialized || typeof window === 'undefined') {
        return;
    }

    initialized = true;
    locale.value = initialLocale();
    document.documentElement.lang = locale.value;

    watch(
        locale,
        async (value) => {
            document.documentElement.lang = value;
            await nextTick();
            window.setTimeout(() => applyLanguage(), 0);
        },
        { immediate: true },
    );

    observer = new MutationObserver((mutations) => {
        if (locale.value !== 'en') {
            return;
        }

        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.TEXT_NODE) {
                    translateTextNode(node as Text);
                    return;
                }

                if (node.nodeType === Node.ELEMENT_NODE) {
                    applyLanguage(node as Element);
                }
            });
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });

    window.addEventListener('storage', (event) => {
        if (event.key === storageKey && (event.newValue === 'sk' || event.newValue === 'en')) {
            locale.value = event.newValue;
        }
    });
}
