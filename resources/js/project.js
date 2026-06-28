import { createApp } from 'vue';
import ProjectDetail from './ProjectDetail.vue';

const appEl = document.getElementById('project-app');
if (appEl) {
    const projectId = appEl.getAttribute('data-project-id');
    const app = createApp(ProjectDetail, { projectId: Number(projectId) });
    app.mount('#project-app');
}
