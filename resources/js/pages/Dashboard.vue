<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { type BreadcrumbItem } from '@/types'
import { useLanguage } from '@/composables/useLanguage'

type StudentLesson = {
  id: number
  title: string
  course_title: string | null
  order: number
  href: string
}

type StudentDashboard = {
  stats: {
    courses: number
    progress: number
    attempts: number
  }
  continue_lessons: StudentLesson[]
  next_test: {
    title: string
    course_title: string | null
    href: string
  } | null
}

type ManagerCourse = {
  id: number
  title: string
  lessons_count: number
  students_count: number
  href: string
}

type ManagerDashboard = {
  stats: {
    courses: number
    lessons: number
    students: number
    attempts: number
  }
  recent_courses: ManagerCourse[]
  primary_action: {
    label: string
    href: string
  }
}

const props = defineProps<{
  role: 'admin' | 'teacher' | 'student'
  student: StudentDashboard | null
  manager: ManagerDashboard | null
}>()

const { locale } = useLanguage()
const isManager = computed(() => props.role === 'admin' || props.role === 'teacher')

const tr = computed(() => {
  if (locale.value === 'en') {
    return {
      dashboard: 'Dashboard',
      backHome: '← Back to home',
      studentIntro: 'Quick overview of your courses, progress, and tests. Continue where you left off.',
      managerIntro: props.role === 'admin'
        ? 'Overview of all courses, students, lessons, and test activity.'
        : 'Overview of your courses, students, lessons, and test activity.',
      openCourses: 'Open courses →',
      createCourse: 'Create course →',
      myCourses: 'My courses',
      activeEnrollments: 'Active enrollments',
      progress: 'Progress',
      completedLessons: 'Completed lessons',
      tests: 'Tests',
      latestAttempts: 'Latest attempts',
      latestLessons: 'Latest lessons',
      continue: 'Continue →',
      nextTest: 'Next test',
      checkKnowledge: 'Check your knowledge and view your results.',
      startTest: 'Start test →',
      noLessons: 'No lessons to continue yet.',
      noTest: 'No available test yet.',
      managedCourses: 'Managed courses',
      totalLessons: 'Total lessons',
      enrolledStudents: 'Enrolled students',
      testAttempts: 'Test attempts',
      coursesToManage: 'Courses to manage',
      manage: 'Manage →',
      students: 'students',
      lessons: 'lessons',
      noManagedCourses: 'No courses created yet.',
      adminPanel: 'Management summary',
      adminHint: 'Open a course to edit lessons, tests, access code, and see student results.',
    }
  }

  return {
    dashboard: 'Prehľad',
    backHome: '← Späť na úvod',
    studentIntro: 'Rýchly prehľad kurzov, progresu a testov. Pokračujte tam, kde ste skončili.',
    managerIntro: props.role === 'admin'
      ? 'Prehľad všetkých kurzov, študentov, lekcií a testovej aktivity.'
      : 'Prehľad vašich kurzov, študentov, lekcií a testovej aktivity.',
    openCourses: 'Otvoriť kurzy →',
    createCourse: 'Vytvoriť kurz →',
    myCourses: 'Moje kurzy',
    activeEnrollments: 'Aktívne zápisy',
    progress: 'Progres',
    completedLessons: 'Dokončené lekcie',
    tests: 'Testy',
    latestAttempts: 'Posledné pokusy',
    latestLessons: 'Posledné lekcie',
    continue: 'Pokračovať →',
    nextTest: 'Najbližší test',
    checkKnowledge: 'Overte si vedomosti a pozrite si výsledky.',
    startTest: 'Spustiť test →',
    noLessons: 'Zatiaľ nie sú žiadne lekcie na pokračovanie.',
    noTest: 'Zatiaľ nie je dostupný žiadny test.',
    managedCourses: 'Spravované kurzy',
    totalLessons: 'Všetky lekcie',
    enrolledStudents: 'Zapísaní študenti',
    testAttempts: 'Pokusy testov',
    coursesToManage: 'Kurzy na správu',
    manage: 'Spravovať →',
    students: 'študenti',
    lessons: 'lekcie',
    noManagedCourses: 'Zatiaľ nie sú vytvorené žiadne kurzy.',
    adminPanel: 'Manažérsky prehľad',
    adminHint: 'Otvorte kurz a upravte lekcie, testy, prístupový kód alebo výsledky študentov.',
  }
})

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: tr.value.dashboard, href: '/dashboard' },
])

const primaryAction = computed(() => {
  if (isManager.value && props.manager?.primary_action) {
    return props.manager.primary_action
  }

  return { label: tr.value.openCourses, href: '/courses' }
})
</script>

<template>
  <Head :title="tr.dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="relative min-h-[calc(100vh-4rem)] bg-[#f7f4ee] text-slate-900">
      <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_5%_10%,rgba(251,191,36,0.16),transparent_60%),radial-gradient(70%_80%_at_95%_20%,rgba(56,189,248,0.12),transparent_60%)]"></div>

      <div class="px-6 py-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <Link href="/" class="inline-flex items-center gap-2 text-slate-500 transition hover:text-slate-800">
              {{ tr.backHome }}
            </Link>
            <h1 class="mt-3 text-3xl font-display">{{ tr.dashboard }}</h1>
            <p class="mt-2 max-w-2xl text-slate-600">
              {{ isManager ? tr.managerIntro : tr.studentIntro }}
            </p>
          </div>

          <Link
            :href="primaryAction.href"
            class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-white transition hover:bg-slate-800"
          >
            {{ isManager && role === 'teacher' ? tr.createCourse : primaryAction.label }}
          </Link>
        </div>

        <template v-if="isManager && manager">
          <div class="mt-8 grid gap-4 lg:grid-cols-4">
            <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
              <div class="text-xs uppercase tracking-wide text-slate-500">{{ tr.managedCourses }}</div>
              <div class="mt-3 text-3xl font-semibold">{{ manager.stats.courses }}</div>
            </div>
            <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
              <div class="text-xs uppercase tracking-wide text-slate-500">{{ tr.totalLessons }}</div>
              <div class="mt-3 text-3xl font-semibold">{{ manager.stats.lessons }}</div>
            </div>
            <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
              <div class="text-xs uppercase tracking-wide text-slate-500">{{ tr.enrolledStudents }}</div>
              <div class="mt-3 text-3xl font-semibold">{{ manager.stats.students }}</div>
            </div>
            <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
              <div class="text-xs uppercase tracking-wide text-slate-500">{{ tr.testAttempts }}</div>
              <div class="mt-3 text-3xl font-semibold">{{ manager.stats.attempts }}</div>
            </div>
          </div>

          <div class="mt-6 grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
              <div class="text-sm font-medium">{{ tr.coursesToManage }}</div>
              <div class="mt-4 space-y-3 text-sm text-slate-700">
                <Link
                  v-for="course in manager.recent_courses"
                  :key="course.id"
                  :href="course.href"
                  class="flex items-center justify-between rounded-xl bg-[#f8fafc] px-4 py-3 ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-sm"
                >
                  <div>
                    <div class="font-medium">{{ course.title }}</div>
                    <div class="text-xs text-slate-500">
                      {{ course.lessons_count }} {{ tr.lessons }} · {{ course.students_count }} {{ tr.students }}
                    </div>
                  </div>
                  <span class="text-xs text-slate-500">{{ tr.manage }}</span>
                </Link>

                <div v-if="manager.recent_courses.length === 0" class="rounded-xl bg-[#f8fafc] p-4 text-sm text-slate-500 ring-1 ring-black/5">
                  {{ tr.noManagedCourses }}
                </div>
              </div>
            </div>

            <div class="rounded-3xl bg-slate-900 p-6 text-white">
              <div class="text-sm text-white/70">{{ tr.adminPanel }}</div>
              <div class="mt-2 text-xl font-semibold">{{ tr.managedCourses }}</div>
              <div class="mt-2 text-sm text-white/70">{{ tr.adminHint }}</div>
              <Link
                :href="role === 'teacher' ? '/teacher/courses/create' : '/courses'"
                class="mt-5 inline-flex items-center justify-center rounded-xl bg-amber-400 px-5 py-2 font-medium text-slate-900 transition hover:bg-amber-300"
              >
                {{ role === 'teacher' ? tr.createCourse : tr.openCourses }}
              </Link>
            </div>
          </div>
        </template>

        <template v-else-if="student">
          <div class="mt-8 grid gap-4 lg:grid-cols-3">
            <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
              <div class="text-xs uppercase tracking-wide text-slate-500">{{ tr.myCourses }}</div>
              <div class="mt-3 text-3xl font-semibold">{{ student.stats.courses }}</div>
              <div class="mt-1 text-sm text-slate-500">{{ tr.activeEnrollments }}</div>
            </div>
            <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
              <div class="text-xs uppercase tracking-wide text-slate-500">{{ tr.progress }}</div>
              <div class="mt-3 text-3xl font-semibold">{{ student.stats.progress }}%</div>
              <div class="mt-1 text-sm text-slate-500">{{ tr.completedLessons }}</div>
            </div>
            <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
              <div class="text-xs uppercase tracking-wide text-slate-500">{{ tr.tests }}</div>
              <div class="mt-3 text-3xl font-semibold">{{ student.stats.attempts }}</div>
              <div class="mt-1 text-sm text-slate-500">{{ tr.latestAttempts }}</div>
            </div>
          </div>

          <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
              <div class="text-sm font-medium">{{ tr.latestLessons }}</div>
              <div class="mt-4 space-y-3 text-sm text-slate-700">
                <Link
                  v-for="lesson in student.continue_lessons"
                  :key="lesson.id"
                  :href="lesson.href"
                  class="flex items-center justify-between rounded-xl bg-[#f8fafc] px-4 py-3 ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-sm"
                >
                  <div>
                    <div class="font-medium">{{ lesson.title }}</div>
                    <div class="text-xs text-slate-500">{{ lesson.course_title }} · {{ tr.lessons }} {{ lesson.order }}</div>
                  </div>
                  <span class="text-xs text-slate-500">{{ tr.continue }}</span>
                </Link>

                <div v-if="student.continue_lessons.length === 0" class="rounded-xl bg-[#f8fafc] p-4 text-sm text-slate-500 ring-1 ring-black/5">
                  {{ tr.noLessons }}
                </div>
              </div>
            </div>

            <div class="rounded-3xl bg-slate-900 p-6 text-white">
              <div class="text-sm text-white/70">{{ tr.nextTest }}</div>
              <template v-if="student.next_test">
                <div class="mt-2 text-lg font-semibold">{{ student.next_test.title }}</div>
                <div class="mt-2 text-sm text-white/70">{{ tr.checkKnowledge }}</div>
                <Link
                  :href="student.next_test.href"
                  class="mt-4 inline-flex items-center justify-center rounded-xl bg-amber-400 px-5 py-2 font-medium text-slate-900 transition hover:bg-amber-300"
                >
                  {{ tr.startTest }}
                </Link>
              </template>
              <div v-else class="mt-2 text-sm text-white/70">{{ tr.noTest }}</div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </AppLayout>
</template>
