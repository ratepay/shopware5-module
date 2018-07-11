pipeline {
    agent {
        label "php"
    }

    triggers {
        pollSCM('H */4 * * 1-5')
    }

    stages {
        stage('Build') {
            steps {
                echo "[STAGE] Build"
                sh "if [ -d build ]; then rm -rf build ; fi"
                sh "mkdir -p build/logs"
            }
        }
        stage('Test') {
            steps {
                echo "[STAGE] Test"
                sh (
                    returnStatus: true,
                    script: "phpcs --report=checkstyle --report-file=./build/logs/checkstyle.xml --standard=PSR2 --extensions=php ./"
                )

                sh (
                    returnStatus: true,
                    script: "phpmd ./ xml codesize,cleancode,design,naming,unusedcode,controversial --reportfile build/logs/pmd.xml"
                )

                // sh '''phpunit --log-junit=build/junit.xml \
                //     --coverage-html=build/coverage \
                //     --coverage-clover=build/clover.xml'''
            }
        }
        stage('Report') {
            steps {
                echo "[STAGE] Report"
                checkstyle canComputeNew: false, defaultEncoding: '', healthy: '', pattern: 'build/logs/checkstyle.xml', unHealthy: ''
                pmd canComputeNew: false, defaultEncoding: '', healthy: '', pattern: 'build/logs/pmd.xml', unHealthy: ''
                // junit 'build/junit.xml'
            }
        }
        stage('Sonarqube') {
            agent { label "master" }
            steps {
                script {
                    withSonarQubeEnv('SonarQube_local') {
                        sh '''sonar-scanner \
                            -Dsonar.projectKey=devmo-shopware5-module \
                            -Dsonar.sources=./ \
                            -Dsonar.host.url=$SONAR_HOST_URL \
                            -Dsonar.login=$SONAR_AUTH_TOKEN'''
                    }
                }
            }
        }
        stage('Deploy') {
            steps {
                echo "[STAGE] Deploy"
            }
        }
    }
}